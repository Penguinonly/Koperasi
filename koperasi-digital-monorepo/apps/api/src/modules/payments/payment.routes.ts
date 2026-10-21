import crypto from "node:crypto";
import { Router } from "express";
import { z } from "zod";

const generateQrSchema = z.object({
  customerId: z.string(),
  loanId: z.string(),
  scheduleIds: z.array(z.string()).min(1),
  coverageMode: z.enum(["ONE_DAY", "TWO_DAYS", "ARREARS_ACCUMULATED", "CUSTOM"]),
  expectedAmount: z.number().positive()
});

export const paymentRouter = Router();

paymentRouter.post("/qr", (request, response) => {
  const payload = generateQrSchema.parse(request.body);
  const expiresAt = new Date(Date.now() + 5 * 60 * 1000);
  const token = crypto.randomUUID();

  response.status(201).json({
    qrId: crypto.randomUUID(),
    token,
    expiresAt: expiresAt.toISOString(),
    qrPayload: {
      type: "KOPERASI_PAYMENT",
      version: 1,
      token,
      customerId: payload.customerId,
      loanId: payload.loanId
    }
  });
});

paymentRouter.post("/scan", (request, response) => {
  response.status(201).json({
    verified: true,
    paymentId: crypto.randomUUID(),
    qrToken: request.body.token,
    scannedAt: new Date().toISOString(),
    message: "Pembayaran tercatat dari scan QR nasabah."
  });
});
