import cors from "cors";
import "dotenv/config";
import express from "express";
import { attendanceRouter } from "./modules/attendance/attendance.routes.js";
import { loanRouter } from "./modules/loans/loan.routes.js";
import { paymentRouter } from "./modules/payments/payment.routes.js";

const app = express();
const port = Number(process.env.API_PORT ?? 4000);

app.use(cors());
app.use(express.json({ limit: "1mb" }));

app.get("/health", (_request, response) => {
  response.json({
    ok: true,
    service: "koperasi-digital-api",
    timestamp: new Date().toISOString()
  });
});

app.use("/api/attendance", attendanceRouter);
app.use("/api/loans", loanRouter);
app.use("/api/payments", paymentRouter);

app.listen(port, () => {
  console.log(`Koperasi Digital API running on http://localhost:${port}`);
});
