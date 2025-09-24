<?php

namespace App\Service;

class PerformanceMetricsService
{
    // metas simples (podés cargarlas de BD/config)
    private float $metaTmr = 24.0;       // horas
    private float $metaSla = 90.0;       // %
    private int $metaCerrados = 30;    // por período

    public function scoreUser(array $agg): array
    {
        $cerr = (int)($agg['cerrados'] ?? 0);
        $tmr = (float)($agg['tmrHoras'] ?? 0);
        $sla = (float)($agg['slaPct'] ?? 0);
        $reab = (float)($agg['reabPct'] ?? 0);

        $up = fn($v, $m) => max(0, min(120, ($m > 0 ? $v / $m : 0) * 100));
        $down = fn($v, $m) => max(0, min(120, ($v > 0 ? $m / $v : ($m > 0 ? 1 : 0)) * 100));

        $prod = ($up($cerr, $this->metaCerrados) + $down($tmr, $this->metaTmr) + $up($sla, $this->metaSla)) / 3;
        $cal = $down($reab, 5.0); // meta 5% reabiertos
        $colab = 100; // si no medís colaboración, neutro
        $disc = 100;

        $final = 0.40 * $prod + 0.30 * $cal + 0.20 * $colab + 0.10 * $disc;
        $final = min(100, $final);

        return [
            'raw' => compact('cerr', 'tmr', 'sla', 'reab'),
            'prod' => round($prod, 1),
            'calidad' => round($cal, 1),
            'colaboracion' => round($colab, 1),
            'disciplina' => round($disc, 1),
            'final' => round($final, 1)
        ];
    }

    public function rankUsers(array $rows): array
    {
        $ranked = [];
        foreach ($rows as $r) {
            $item = [
                'userId' => $r['userId'],
                'username' => $r['username'] ?? null,
                'nombre' => $r['nombre'] ?? null,
                'apellido' => $r['apellido'] ?? null,
                'score' => $this->scoreUser($r),
            ];
            // Preserve extra fields if present
            foreach (['ticketsAssigned','ticketsCompleted','ticketAvgResolutionMin','lastTicketUpdate'] as $k) {
                if (array_key_exists($k, $r)) { $item[$k] = $r[$k]; }
            }
            $ranked[] = $item;
        }
        usort($ranked, fn($a, $b) => $b['score']['final'] <=> $a['score']['final']);
        return $ranked;
    }
}
