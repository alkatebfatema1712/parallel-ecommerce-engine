<?php

namespace App\Http\Controllers;

class LoadBalancerController extends Controller
{
    /*
     * Load Balancing Simulation
     *
     * active: عدد الاتصالات الحالية على السيرفر.
     * speed_factor: كلما كان الرقم أصغر كان السيرفر أسرع في تحرير الاتصالات.
     * healthy: نتيجة Health Check التي تحدد هل السيرفر متاح لاستقبال الطلبات أم لا.
     */
    private array $servers = [
        [
            'name' => 'Server-1 (High Spec)',
            'active' => 0,
            'speed_factor' => 5,
            'healthy' => true,
        ],
        [
            'name' => 'Server-2 (Medium Spec)',
            'active' => 0,
            'speed_factor' => 8,
            'healthy' => true,
        ],
        [
            'name' => 'Server-3 (Low Spec)',
            'active' => 0,
            'speed_factor' => 12,
            'healthy' => true,
        ],
    ];

    public function simulateLeastConnections()
    {
        $requests = 1000;
        $log = [];
        $maxLog = 20;
        $unhealthyEvents = 0;

        for ($i = 1; $i <= $requests; $i++) {

            /*
             * 1) Health Check
             * لا يتم إرسال الطلب إلى أي سيرفر قبل التأكد أنه Healthy.
             */
            foreach ($this->servers as &$server) {
                $server['healthy'] = $this->simulateHealthCheck($server);

                if (!$server['healthy']) {
                    $unhealthyEvents++;
                }
            }
            unset($server);

            /*
             * 2) Simulate completed connections
             * نحاكي انتهاء بعض الطلبات القديمة.
             * السيرفر الأسرع يحرر اتصالاته بشكل أسرع.
             */
            foreach ($this->servers as &$server) {
                if ($server['active'] > 0 && rand(1, $server['speed_factor']) === 1) {
                    $server['active']--;
                }
            }
            unset($server);

            /*
             * 3) Filter healthy servers only
             * يتم استبعاد السيرفرات غير الصحية من عملية الاختيار.
             */
            $healthyServers = array_filter(
                $this->servers,
                fn ($server) => $server['healthy'] === true
            );

            /*
             * في حال أصبحت كل السيرفرات غير صحية داخل المحاكاة،
             * نستخدم Degraded Mode باختيار الأقل حملاً بدل إيقاف الاختبار.
             * في الأنظمة الحقيقية يمكن هنا إرجاع 503 Service Unavailable.
             */
            $degradedMode = false;
            if (count($healthyServers) === 0) {
                $degradedMode = true;
                $healthyServers = $this->servers;
            }

            /*
             * 4) Least Connections
             * ترتيب السيرفرات حسب عدد الاتصالات النشطة واختيار الأقل انشغالاً.
             */
            usort($healthyServers, fn ($a, $b) => $a['active'] <=> $b['active']);

            $selectedServerName = $healthyServers[0]['name'];
            $selectedServer = null;

            /*
             * 5) Update original server load
             * بعد اختيار السيرفر المناسب تتم زيادة عدد الاتصالات عليه.
             */
            foreach ($this->servers as &$server) {
                if ($server['name'] === $selectedServerName) {
                    $server['active']++;
                    $selectedServer = $server;
                    break;
                }
            }
            unset($server);

            /*
             * 6) Log sample requests
             * نسجل أول 20 طلباً فقط حتى تبقى النتيجة واضحة.
             */
            if ($i <= $maxLog && $selectedServer !== null) {
                $log[] = [
                    'request_id' => $i,
                    'assigned_to' => $selectedServer['name'],
                    'active_connections_at_moment' => $selectedServer['active'],
                    'server_health' => $selectedServer['healthy'] ? 'Healthy' : 'Unhealthy',
                    'mode' => $degradedMode ? 'Degraded Mode' : 'Normal Mode',
                    'selection_reason' => $degradedMode
                        ? 'No healthy server was available, so the least loaded server was selected to keep the simulation running.'
                        : 'Selected because it is healthy and has the fewest active connections.',
                ];
            }
        }

        return response()->json([
            'architecture' => 'Distributed System Simulation (Load Balancing)',
            'algorithm_used' => 'Health Check + Least Connections',
            'total_simulated_requests' => $requests,
            'health_check_strategy' => 'Servers are checked before routing. Unhealthy servers are skipped when possible.',
            'unhealthy_events_detected' => $unhealthyEvents,
            'final_servers_load_status' => $this->servers,
            'sample_live_distribution_log' => $log,
            'justification' => 'تم استخدام Health Check أولاً لاستبعاد السيرفرات غير الصحية، ثم تم تطبيق Least Connections لاختيار السيرفر الصحي الأقل انشغالاً. وفي حال عدم توفر أي سيرفر صحي داخل المحاكاة يتم استخدام Degraded Mode بدلاً من إيقاف الاختبار.',
        ]);
    }

    /*
     * محاكاة Health Check.
     * في التطبيق الحقيقي قد يكون الفحص TCP أو HTTP ويعيد 200 OK.
     */
    private function simulateHealthCheck(array $server): bool
    {
        $maxAllowedConnections = match ($server['name']) {
            'Server-1 (High Spec)' => 600,
            'Server-2 (Medium Spec)' => 450,
            'Server-3 (Low Spec)' => 300,
            default => 300,
        };

        if ($server['active'] >= $maxAllowedConnections) {
            return false;
        }

        /* احتمال بسيط جداً لتعطل مؤقت لمحاكاة واقع الأنظمة. */
        return rand(1, 100) > 2;
    }
}
