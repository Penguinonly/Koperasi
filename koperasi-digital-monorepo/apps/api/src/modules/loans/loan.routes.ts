import { Router } from "express";
import { generateCollectionDates, getLoanRiskColor } from "@koperasi/shared";

export const loanRouter = Router();

loanRouter.post("/preview-schedule", (request, response) => {
  const startDate = new Date(request.body.startDate ?? Date.now());
  const dates = generateCollectionDates(startDate);

  response.json({
    termDays: dates.length,
    firstDueDate: dates[0],
    expectedEndDate: dates.at(-1),
    dates
  });
});

loanRouter.get("/risk-preview/:arrearsDays", (request, response) => {
  const arrearsDays = Number(request.params.arrearsDays);

  response.json({
    arrearsDays,
    riskColor: getLoanRiskColor(arrearsDays)
  });
});
