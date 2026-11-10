<?php

namespace App\QaDemo;

use Carbon\CarbonImmutable;

final class QaDemoSeeder
{
    public function seed(QaDemoService $qa): bool
    {
        return $qa->connection()->transaction(fn (): bool => $this->seedInsideTransaction($qa), 3);
    }

    private function seedInsideTransaction(QaDemoService $qa): bool
    {
        if ($qa->meta('examples_seeded') || $qa->equipment() !== [] || $qa->runs() !== []) {
            return false;
        }
        $catalog = collect($qa->catalog());
        if ($catalog->isEmpty()) {
            return false;
        }
        $today = CarbonImmutable::now((string) config('qa_demo.timezone', 'America/El_Salvador'));
        $last = $today->startOfDay()->subDay();
        while (! in_array($last->dayOfWeekIso, [1, 3, 5], true)) {
            $last = $last->subDay();
        }
        // Si el control de hoy ya podría haberse medido, la demostración incluye hoy.
        if (in_array($today->dayOfWeekIso, [1, 3, 5], true) && $today->hour >= 9) {
            $last = $today->startOfDay();
        }
        $dates = [];
        for ($d = $last; count($dates) < 13; $d = $d->subDay()) {
            if (in_array($d->dayOfWeekIso, [1, 3, 5], true)) {
                $dates[] = $d;
            }
        }
        $dates = array_reverse($dates);
        $opening = $dates[0]->subDay();
        $label = 'QCQ-'.$today->format('ym');
        $equipmentId = $qa->createEquipment([
            'code' => 'QUIMICA-01', 'name' => 'Química 01', 'model' => 'Mindray BS-240',
            'notes' => 'Analizador de química clínica.',
        ]);
        $specs = [
            'GLUCOSA EN AYUNA' => [[60, 54, 66, 59.4], [110, 99, 121, 109.2], [300, 270, 330, 298]],
            'CREATININA SERICA' => [[.8, .64, .96, .82], [1.8, 1.53, 2.07, 1.78], [4.5, 3.825, 5.175, 4.48]],
            'COLESTEROL TOTAL' => [[100, 90, 110, 98], [200, 180, 220, 199], [300, 270, 330, 301]],
            'TRIGLICERIDOS' => [[75, 63.75, 86.25, 75], [150, 127.5, 172.5, 151], [300, 255, 345, 298]],
            'GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)' => [[25, 20, 30, 24.8], [70, 59.5, 80.5, 68.3], [180, 153, 207, 181]],
        ];
        $selected = [];
        foreach ($specs as $name => $spec) {
            $entry = $catalog->firstWhere('name', $name);
            if ($entry) {
                $selected[] = [$entry, $spec];
            }
        }
        if ($selected === []) {
            $entry = $catalog->first();
            $selected[] = [$entry, [[60, 54, 66, 59.4], [110, 99, 121, 109.2], [300, 270, 330, 298]]];
        }
        $levels = [];
        foreach (QaDemoService::LEVELS as $key => $level) {
            $levels[$key] = ['lot' => $label.match ($key) {'low' => 'L', 'medium' => 'M', default => 'H'},
                'product' => 'Suero de control multianalito', 'expires_on' => $today->addMonths(3)->toDateString(),
                'opened_on' => $opening->toDateString(), 'stability_days' => 60];
        }
        $analytes = [];
        foreach ($selected as [$entry, $spec]) {
            $targets = [];
            foreach (array_keys(QaDemoService::LEVELS) as $i => $key) {
                $targets[$key] = ['target' => $spec[$i][0], 'min' => $spec[$i][1], 'max' => $spec[$i][2]];
            }
            $analytes[] = ['catalog_id' => $entry['id'], 'method' => 'Método del equipo',
                'unit' => $entry['unit'] ?: 'Unidad del control', 'targets' => $targets];
        }
        $configurationId = $qa->saveConfiguration(['equipment_id' => $equipmentId, 'configured_by' => 'Rafael Hernández',
            'label' => $label, 'levels' => $levels, 'analytes' => $analytes]);
        $firstId = $selected[0][0]['id'];
        $isGlucose = $selected[0][0]['name'] === 'GLUCOSA EN AYUNA';
        foreach ($dates as $i => $date) {
            $values = [];
            foreach ($selected as [$entry, $spec]) {
                foreach (array_keys(QaDemoService::LEVELS) as $j => $key) {
                    $v = $i === 12 ? $spec[$j][3] : round($spec[$j][0] * [1.007, .995, 1.014, .998, 1.009][$i % 5], 3);
                    $values[$entry['id']][$key] = str_replace('.', ',', (string) $v);
                }
            }
            $outlier = $i === 11;
            if ($outlier) {
                $values[$firstId]['high'] = (string) ($isGlucose ? 337 : $selected[0][1][2][2] * 1.05);
            }
            $runId = $qa->recordRun(['configuration_id' => $configurationId, 'measured_at' => $date->setTime(7, 45)->format('Y-m-d\TH:i'),
                'operator' => 'Daniela Martínez', 'kind' => 'routine', 'notes' => 'Control habitual de química.', 'values' => $values]);
            if ($outlier) {
                $qa->reviewRun($runId, ['reviewer' => 'Rafael Hernández', 'decision' => 'followup',
                    'notes' => 'Se conserva la lectura fuera de límites. Se documenta la revisión de identificación y preparación del control.']);
                $repeat = $qa->recordRun(['configuration_id' => $configurationId, 'parent_run_id' => $runId,
                    'measured_at' => $date->setTime(8, 12)->format('Y-m-d\TH:i'), 'operator' => 'Daniela Martínez',
                    'kind' => 'repeat', 'notes' => 'Sólo se repitió la prueba en nivel alto. La lectura original permanece.',
                    'values' => [$firstId => ['high' => (string) ($isGlucose ? 302 : $selected[0][1][2][0])]]]);
                $qa->reviewRun($repeat, ['reviewer' => 'Rafael Hernández', 'decision' => 'reviewed',
                    'notes' => 'Repetición revisada.']);
                $qa->reviewRun($runId, ['reviewer' => 'Rafael Hernández', 'decision' => 'reviewed',
                    'notes' => 'Revisión documentada y vinculada con repetición. El valor inicial se conserva.']);
            } elseif ($i < 12) {
                $qa->reviewRun($runId, ['reviewer' => 'Rafael Hernández', 'decision' => 'reviewed',
                    'notes' => 'Control revisado.']);
            }
        }
        $qa->setMeta('examples_seeded', $today->toIso8601String());
        return true;
    }
}
