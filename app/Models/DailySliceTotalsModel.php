<?php


namespace App\Models;


class DailySliceTotalsModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slice_totals_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($queryBuilder)
    {
        parent::__construct($queryBuilder);

        $this->setTable(self::TABLE_PREFIX . date('Y_m_d'));
        $this->createTable($this->getTable());
    }

    private function createTable($name)
    {
        if ($this->shema()->hasTable($name)) {
            return;
        }

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedSmallInteger('metric_id');
            $table->unsignedSmallInteger('slice_id');
            $table->float('value');

            $table->unique(['metric_id', 'slice_id']);
        });
    }

    public function addValue(int $metricId, int $sliceId, float $value)
    {
        if (!$value) {
            return false;
        }

        $exist = $this->qb()->where('metric_id', $metricId)->where('slice_id', $sliceId)->first();
        if (empty($exist)) {

            // Create new row
            return $this->insert(['metric_id' => $metricId, 'slice_id' => $sliceId, 'value' => $value]);
        }

        $this->increment($exist['id'], 'value', $value);
        return true;
    }

    public function getAllValues()
    {
        return $this->qb()->selectRaw('slice_id as id, sum(value) as value')
            ->groupBy('slice_id')
            ->get();
    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }
}