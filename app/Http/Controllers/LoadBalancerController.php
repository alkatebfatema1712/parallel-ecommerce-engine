<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoadBalancerController extends Controller
{
    // تعريف السيرفرات مع تفاوت افتراضي في قدرتها وسرعتها (حالات واقعية)
  // 1. تعديل قيم المعاملات لجعل السيرفرات تنشغل بشكل واقعي
    private $servers = [
        ['name' => 'Server-1 (High Spec)', 'active' => 0, 'speed_factor' => 5],  // أصبح ينشغل أكثر
        ['name' => 'Server-2 (Medium Spec)', 'active' => 0, 'speed_factor' => 8], // أبطأ قليلاً
        ['name' => 'Server-3 (Low Spec)', 'active' => 0, 'speed_factor' => 12], // الأبطأ
    ];

    public function simulateLeastConnections()
    {
        $requests = 1000; // قللنا العدد الإجمالي فقط لتتركز القيم في العينة
        $log = [];
        $maxLog = 20; // سنراقب أول 20 طلباً بالترتيب

        for ($i = 1; $i <= $requests; $i++) {
            
            // محاكاة تفريغ عشوائي بنسبة أقل لكي تتراكم الاتصالات النشطة
            foreach ($this->servers as &$server) {
                if ($server['active'] > 0 && rand(1, $server['speed_factor']) == 1) {
                    $server['active']--; 
                }
            }

            // ترتيب تصاعدي حسب الأقل انشغالاً
            usort($this->servers, fn($a, $b) => $a['active'] <=> $b['active']);

            // توجيه الطلب
            $this->servers[0]['active']++;

            // تسجيل أول 20 طلباً
            if ($i <= $maxLog) {
                $log[] = [
                    'request_id' => $i,
                    'assigned_to' => $this->servers[0]['name'],
                    'active_connections_at_moment' => $this->servers[0]['active']
                ];
            }
        }

            return response()->json([
            'architecture' => 'Distributed System Simulation (Load Balancing)',
            'algorithm_used' => 'Least Connections (الأقل اتصالاً ديناميكياً)',
            'total_simulated_requests' => $requests,
            'final_servers_load_status' => $this->servers,
            'sample_live_distribution_log' => $log,
            'justification' => 'تم اختيار Least Connections لأنها تضمن عدم تحميل أي سيرفر فوق طاقته، وتوجه الضغط تلقائياً للسيرفر الأسرع أو الأقل انشغالاً في نفس اللحظة.'
        ]);
    }
}