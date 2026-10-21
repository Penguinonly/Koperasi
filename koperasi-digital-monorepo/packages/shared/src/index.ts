export type UserRole = "CUSTOMER" | "COLLECTOR" | "ADMIN" | "MASTER_ADMIN";
export type Platform = "ANDROID" | "WEB" | "DESKTOP";
export type LoanRiskColor = "BLUE" | "YELLOW" | "RED" | "DARK";
export type AttendanceSlot = "MORNING_IN" | "NOON_OUT" | "AFTERNOON_IN" | "NIGHT_OUT" | "UNKNOWN";
export type AttendanceCalendarColor = "BLUE" | "YELLOW" | "RED";

export const USER_ROLES: UserRole[] = ["CUSTOMER", "COLLECTOR", "ADMIN", "MASTER_ADMIN"];
export const DESKTOP_ROLES: UserRole[] = ["COLLECTOR", "ADMIN", "MASTER_ADMIN"];

export const ATTENDANCE_WINDOWS = {
  MORNING_IN: { startMinute: 7 * 60, endMinute: 10 * 60 + 59 },
  NOON_OUT: { startMinute: 11 * 60, endMinute: 15 * 60 + 59 },
  AFTERNOON_IN: { startMinute: 16 * 60, endMinute: 17 * 60 + 29 },
  NIGHT_OUT: { startMinute: 17 * 60 + 30, endMinute: 23 * 60 + 59 }
} as const;

export const COLLECTION_TERM_DAYS = 60;

export function canAccessPlatform(role: UserRole, platform: Platform) {
  if (platform === "ANDROID") {
    return true;
  }

  return DESKTOP_ROLES.includes(role);
}

export function getLoanRiskColor(arrearsDays: number): LoanRiskColor {
  if (arrearsDays <= 0) {
    return "BLUE";
  }

  if (arrearsDays <= 2) {
    return "YELLOW";
  }

  if (arrearsDays <= 9) {
    return "RED";
  }

  return "DARK";
}

export function getAttendanceSlot(date: Date): AttendanceSlot {
  const minuteOfDay = date.getHours() * 60 + date.getMinutes();

  for (const [slot, window] of Object.entries(ATTENDANCE_WINDOWS)) {
    if (minuteOfDay >= window.startMinute && minuteOfDay <= window.endMinute) {
      return slot as AttendanceSlot;
    }
  }

  return "UNKNOWN";
}

export type AttendanceRecordLike = {
  morningInAt?: string | Date | null;
  noonOutAt?: string | Date | null;
  afternoonInAt?: string | Date | null;
  nightOutAt?: string | Date | null;
};

export function getAttendanceCalendarColor(record?: AttendanceRecordLike | null): AttendanceCalendarColor {
  if (!record) {
    return "RED";
  }

  const slots = [record.morningInAt, record.noonOutAt, record.afternoonInAt, record.nightOutAt];
  const filledSlots = slots.filter(Boolean).length;

  if (filledSlots === 4) {
    return "BLUE";
  }

  if (filledSlots > 0) {
    return "YELLOW";
  }

  return "RED";
}

export function generateCollectionDates(startDate: Date, totalDays = COLLECTION_TERM_DAYS) {
  const dates: string[] = [];
  const cursor = new Date(startDate);

  while (dates.length < totalDays) {
    const isSunday = cursor.getDay() === 0;

    if (!isSunday) {
      dates.push(toDateOnly(cursor));
    }

    cursor.setDate(cursor.getDate() + 1);
  }

  return dates;
}

export function toDateOnly(date: Date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}
