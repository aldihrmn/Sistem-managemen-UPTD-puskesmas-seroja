<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ResolvesEmployee;

    private function meta(Request $request): array
    {
        $request->validate(['month' => 'nullable|integer|between:1,12', 'year' => 'nullable|integer|between:2000,2100']);
        $report = ReportService::monthly((int) $request->query('month', now()->month), (int) $request->query('year', now()->year), [], $this->me()->id);

        return ReportService::meta($report, $request->user());
    }

    public function index(Request $request)
    {
        return view('pegawai.report.index', $this->meta($request));
    }

    public function print(Request $request)
    {
        return view('reports.document', $this->meta($request) + ['autoPrint' => true]);
    }
}
