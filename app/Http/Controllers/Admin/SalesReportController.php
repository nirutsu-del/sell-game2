<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
class SalesReportController extends Controller {
    public function index(Request $request, SalesReportService $service) {
        $period = $service->period($request);
        return response()->view('admin.reports.sales',[
            'period'=>$period,'report'=>$service->summary($period),'bestSellers'=>$service->bestSellers($period),
        ])->header('Cache-Control','private, no-store');
    }
    public function export(Request $request, SalesReportService $service) {
        $period = $service->period($request);
        return response()->streamDownload(function () use ($period,$service) {
            $stream = fopen('php://output','w');
            fwrite($stream,"\xEF\xBB\xBF");
            fputcsv($stream,['เลขรายการ Wallet','เวลาไทย','ประเภท','ทิศทาง Wallet','เลขอ้างอิง','รายละเอียด','จำนวนบาท'],',','"','');
            foreach ($service->query($period)->orderBy('wallet_transactions.id')->lazy(500) as $row) {
                $description = $row->description;
                if (preg_match('/^[\s]*[=+@-]/u',$description) || preg_match('/^[\t\r\n]/',$description)) $description = "'".$description;
                fputcsv($stream,[$row->id,CarbonImmutable::parse($row->created_at)->setTimezone('Asia/Bangkok')->format('Y-m-d H:i:s'),$service->classify($row),$row->type === 'debit' ? 'ตัดออก' : 'เพิ่มเข้า',$row->reference_id,$description,number_format((float)$row->amount,2,'.','')],',','"','');
            }
            fclose($stream);
        },'sales-'.($period['from'] ?? 'all').'-'.($period['to'] ?? 'dates').'.csv',[
            'Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store',
        ]);
    }
}
