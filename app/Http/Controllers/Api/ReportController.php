<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function teamSummary(Request $request): JsonResponse
    {
        $this->authorizeReport($request->user());

        $data = $this->reportService->teamSummary(
            $request->user(),
            $request->query()
        );

        return response()->json(['data' => $data]);
    }

    public function byCategory(Request $request): JsonResponse
    {
        $this->authorizeReport($request->user());

        $data = $this->reportService->byCategory(
            $request->user(),
            $request->query()
        );

        return response()->json(['data' => $data]);
    }

    public function byMember(Request $request): JsonResponse
    {
        $this->authorizeReport($request->user());

        $data = $this->reportService->byMember(
            $request->user(),
            $request->query()
        );

        return response()->json(['data' => $data]);
    }

    private function authorizeReport($user): void
    {
        if (! in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            abort(403, 'Only managers and admins can view reports.');
        }
    }
}