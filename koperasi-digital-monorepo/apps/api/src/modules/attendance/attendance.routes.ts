import { Router } from "express";
import { getAttendanceCalendarColor, getAttendanceSlot } from "@koperasi/shared";

export const attendanceRouter = Router();

attendanceRouter.post("/iot/logs", (request, response) => {
  const occurredAt = new Date(request.body.occurredAt ?? Date.now());
  const slot = getAttendanceSlot(occurredAt);

  response.status(202).json({
    accepted: true,
    deviceCode: request.body.deviceCode,
    employeeId: request.body.employeeId,
    slot,
    calendarColor: getAttendanceCalendarColor({
      morningInAt: slot === "MORNING_IN" ? occurredAt : null,
      noonOutAt: slot === "NOON_OUT" ? occurredAt : null,
      afternoonInAt: slot === "AFTERNOON_IN" ? occurredAt : null,
      nightOutAt: slot === "NIGHT_OUT" ? occurredAt : null
    })
  });
});

attendanceRouter.get("/me", (_request, response) => {
  response.json({
    employeeId: "emp-demo",
    month: "2026-05",
    records: [
      { date: "2026-05-20", status: "COMPLETE", color: "BLUE" },
      { date: "2026-05-21", status: "PARTIAL", color: "YELLOW" },
      { date: "2026-05-22", status: "ABSENT", color: "RED" }
    ]
  });
});
