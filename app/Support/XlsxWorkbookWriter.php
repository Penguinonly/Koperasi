<?php

namespace App\Support;

use RuntimeException;

/**
 * Writes a small, standards-based XLSX workbook without requiring ext-zip.
 * Content is compressed incrementally so transaction rows do not have to be
 * held in memory while the archive is created.
 */
class XlsxWorkbookWriter
{
    /** @param array<string, string|resource> $files */
    public function write(string $path, array $files): void
    {
        $archive = fopen($path, 'wb');
        if ($archive === false) {
            throw new RuntimeException('File laporan Excel tidak dapat dibuat.');
        }

        $centralDirectory = '';
        $entryCount = 0;
        $dosTime = (int) (date('G') << 11 | date('i') << 5 | floor((int) date('s') / 2));
        $dosDate = (int) (((int) date('Y') - 1980) << 9 | (int) date('n') << 5 | (int) date('j'));

        try {
            foreach ($files as $name => $content) {
                $nameBytes = $name;
                $nameLength = strlen($nameBytes);
                $localOffset = ftell($archive);
                if ($localOffset === false) {
                    throw new RuntimeException('Posisi file Excel tidak dapat dibaca.');
                }

                // General purpose bit 3 means sizes and CRC follow the compressed data.
                fwrite($archive, pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0008, 8, $dosTime, $dosDate, 0, 0, 0, $nameLength, 0));
                fwrite($archive, $nameBytes);

                $source = $this->openSource($content, $temporarySource);
                $deflater = deflate_init(ZLIB_ENCODING_RAW, ['level' => 6]);
                if ($deflater === false) {
                    throw new RuntimeException('Kompresi file Excel gagal dimulai.');
                }

                $checksum = hash_init('crc32b');
                $uncompressedSize = 0;
                $compressedSize = 0;

                while (!feof($source)) {
                    $chunk = fread($source, 65536);
                    if ($chunk === false) {
                        throw new RuntimeException('Data laporan Excel gagal dibaca.');
                    }
                    if ($chunk === '') {
                        continue;
                    }

                    hash_update($checksum, $chunk);
                    $uncompressedSize += strlen($chunk);
                    $compressed = deflate_add($deflater, $chunk, ZLIB_NO_FLUSH);
                    if ($compressed === false) {
                        throw new RuntimeException('Kompresi data Excel gagal.');
                    }
                    $compressedSize += strlen($compressed);
                    fwrite($archive, $compressed);
                }

                $lastChunk = deflate_add($deflater, '', ZLIB_FINISH);
                if ($lastChunk === false) {
                    throw new RuntimeException('Kompresi data Excel gagal diselesaikan.');
                }
                $compressedSize += strlen($lastChunk);
                fwrite($archive, $lastChunk);

                if (isset($temporarySource) && $temporarySource) {
                    fclose($source);
                    $temporarySource = false;
                }

                $crc = hexdec(hash_final($checksum));
                fwrite($archive, pack('VVVV', 0x08074b50, $crc, $compressedSize, $uncompressedSize));

                $centralDirectory .= pack(
                    'VvvvvvvVVVvvvvvVV',
                    0x02014b50,
                    20,
                    20,
                    0x0008,
                    8,
                    $dosTime,
                    $dosDate,
                    $crc,
                    $compressedSize,
                    $uncompressedSize,
                    $nameLength,
                    0,
                    0,
                    0,
                    0,
                    0,
                    $localOffset,
                ).$nameBytes;
                $entryCount++;
            }

            $centralOffset = ftell($archive);
            if ($centralOffset === false) {
                throw new RuntimeException('Indeks file Excel tidak dapat dibuat.');
            }

            fwrite($archive, $centralDirectory);
            fwrite($archive, pack('VvvvvVVv', 0x06054b50, 0, 0, $entryCount, $entryCount, strlen($centralDirectory), $centralOffset, 0));
        } finally {
            fclose($archive);
        }
    }

    /** @param string|resource $content
     * @return resource
     */
    private function openSource(mixed $content, ?bool &$temporary): mixed
    {
        if (is_resource($content)) {
            rewind($content);
            $temporary = false;

            return $content;
        }

        $source = fopen('php://temp/maxmemory:1048576', 'w+b');
        if ($source === false) {
            throw new RuntimeException('Buffer laporan Excel tidak dapat dibuat.');
        }

        fwrite($source, $content);
        rewind($source);
        $temporary = true;

        return $source;
    }
}
