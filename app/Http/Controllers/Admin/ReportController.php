<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\ActivityLogger;
use App\Services\ReportExporter;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function meta(Request $request): array
    {
        $request->validate(['month' => 'nullable|integer|between:1,12', 'year' => 'nullable|integer|between:2000,2100']);
        $filters = $request->only(['q', 'department_id', 'position_id', 'profession', 'employment_status']);
        $report = ReportService::monthly((int) $request->query('month', now()->month), (int) $request->query('year', now()->year), $filters);

        return ReportService::meta($report, $request->user());
    }

    public function index(Request $request)
    {
        return view('admin.reports.index', $this->meta($request) + [
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'positions' => Position::orderBy('name')->pluck('name', 'id'),
            'professions' => Employee::whereNotNull('profession')->distinct()->orderBy('profession')->pluck('profession'),
            'statuses' => Employee::whereNotNull('employment_status')->distinct()->orderBy('employment_status')->pluck('employment_status'),
        ]);
    }

    public function print(Request $request)
    {
        $meta = $this->meta($request);
        ActivityLogger::log('report.printed', 'Mencetak laporan bulanan '.$meta['periodLabel']);

        return view('reports.document', $meta + ['autoPrint' => true]);
    }

    public function pdf(Request $request)
    {
        $meta = $this->meta($request);
        ActivityLogger::log('report.pdf', 'Mengunduh laporan PDF '.$meta['periodLabel']);

        return response(ReportExporter::pdf($meta), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-'.$meta['report']['from']->format('Y-m').'.pdf"',
        ]);
    }

    public function excel(Request $request)
    {
        $meta = $this->meta($request);
        $writer = ReportExporter::excel($meta);
        ActivityLogger::log('report.excel', 'Mengekspor laporan Excel '.$meta['periodLabel']);

        return response()->streamDownload(fn () => $writer->save('php://output'), 'laporan-'.$meta['report']['from']->format('Y-m').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
