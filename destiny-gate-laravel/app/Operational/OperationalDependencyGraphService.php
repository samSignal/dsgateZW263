<?php

namespace App\Operational;

class OperationalDependencyGraphService
{
    public static function graph(): array
    {
        return [
            'identity' => ['depends_on' => []],
            'timetable' => ['depends_on' => []],
            'attendance' => ['depends_on' => ['timetable']],
            'lms' => ['depends_on' => ['identity']],
            'exams' => ['depends_on' => []],
            'library' => ['depends_on' => ['identity']],
            'transport' => ['depends_on' => ['identity']],
            'hostel' => ['depends_on' => ['identity']],
        ];
    }

    public static function buildPlan(array $requestedServices = []): array
    {
        $graph = self::graph();
        $services = $requestedServices;
        if (count($services) === 0) $services = array_keys($graph);

        $services = array_values(array_unique(array_filter(array_map(fn ($s) => is_string($s) ? trim($s) : '', $services), fn ($s) => $s !== '')));

        $unknown = array_values(array_filter($services, fn ($s) => !isset($graph[$s])));
        if (count($unknown) > 0) {
            return [
                'ok' => false,
                'code' => 'UNKNOWN_SERVICE',
                'message' => 'Unknown services: ' . implode(', ', $unknown),
                'order' => [],
                'diagnostics' => ['unknown' => $unknown],
            ];
        }

        $expanded = [];
        $stack = $services;
        $visited = [];
        $max = 50;
        while (count($stack) > 0) {
            if (count($expanded) >= $max) break;
            $s = array_pop($stack);
            if (!is_string($s) || $s === '') continue;
            if (isset($visited[$s])) continue;
            $visited[$s] = true;
            $expanded[] = $s;
            foreach (($graph[$s]['depends_on'] ?? []) as $d) {
                if (!isset($visited[$d])) $stack[] = $d;
            }
        }

        $sub = [];
        foreach ($expanded as $s) {
            $sub[$s] = $graph[$s];
        }

        $cycle = self::detectCycle($sub);
        if ($cycle['has_cycle']) {
            return [
                'ok' => false,
                'code' => 'CYCLE',
                'message' => 'Operational dependency cycle detected.',
                'order' => [],
                'diagnostics' => ['cycle_nodes' => $cycle['cycle_nodes']],
            ];
        }

        $order = self::topoSort($sub);

        $steps = [];
        $i = 0;
        foreach ($order as $s) {
            $i++;
            $steps[] = [
                'order' => $i * 10,
                'service' => $s,
                'depends_on' => (array) ($sub[$s]['depends_on'] ?? []),
            ];
        }

        return [
            'ok' => true,
            'code' => 'OK',
            'order' => $order,
            'steps' => $steps,
            'diagnostics' => [
                'services_requested' => $services,
                'services_expanded' => $expanded,
            ],
        ];
    }

    public static function buildRollbackPlan(array $requestedServices = []): array
    {
        $plan = self::buildPlan($requestedServices);
        if (empty($plan['ok'])) return $plan;

        $order = array_values(array_reverse((array) ($plan['order'] ?? [])));
        return [
            'ok' => true,
            'code' => 'OK',
            'order' => $order,
            'diagnostics' => $plan['diagnostics'] ?? null,
        ];
    }

    public static function detectCycle(array $graph): array
    {
        $visited = [];
        $inStack = [];
        $cycleNodes = [];
        $hasCycle = false;

        $dfs = function (string $u) use (&$dfs, &$visited, &$inStack, &$graph, &$hasCycle, &$cycleNodes) {
            $visited[$u] = true;
            $inStack[$u] = true;
            foreach (($graph[$u]['depends_on'] ?? []) as $v) {
                if (!isset($graph[$v])) continue;
                if (!isset($visited[$v])) {
                    $dfs($v);
                } elseif (!empty($inStack[$v])) {
                    $hasCycle = true;
                    $cycleNodes[] = $v;
                }
            }
            $inStack[$u] = false;
        };

        foreach (array_keys($graph) as $n) {
            if ($hasCycle) break;
            if (!isset($visited[$n])) $dfs($n);
        }

        return [
            'has_cycle' => $hasCycle,
            'cycle_nodes' => array_values(array_unique($cycleNodes)),
        ];
    }

    private static function topoSort(array $graph): array
    {
        $in = [];
        $adj = [];
        foreach ($graph as $node => $meta) {
            $in[$node] = $in[$node] ?? 0;
            foreach (($meta['depends_on'] ?? []) as $dep) {
                if (!isset($graph[$dep])) continue;
                $adj[$dep] = $adj[$dep] ?? [];
                $adj[$dep][] = $node;
                $in[$node] = ($in[$node] ?? 0) + 1;
            }
        }

        $queue = [];
        foreach ($in as $n => $deg) {
            if ($deg === 0) $queue[] = $n;
        }

        $out = [];
        while (count($queue) > 0) {
            $u = array_shift($queue);
            $out[] = $u;
            foreach (($adj[$u] ?? []) as $v) {
                $in[$v]--;
                if ($in[$v] === 0) {
                    $queue[] = $v;
                }
            }
        }

        return $out;
    }
}
