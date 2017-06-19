<?php

namespace App\Commands\Raw;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Metrics extends AbstractCommand
{
    const COUNTER_NAME = 'raw_metrics';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {
            $timeStart = time();
            $saved = $this->fetch();
            $this->out("Saved {$saved} daily metrics");
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
        }
    }

    private function fetch()
    {
        $time = date('Y-m-d H:i:s');
        $lastCounter = $this->mysql->dailyCounters->getValue(static::COUNTER_NAME);
        $startId = $lastCounter ? $lastCounter + 1 : 0;

        // Getting maxId bc no order in aggr result
        $maxId = $this->mysql->dailyRawMetrics->getMaxIdForTime($time);
        if ($maxId < $startId) {
            $this->out('No new records in dailyRawMetrics from startId ' . $startId);
            sleep(5);
            return 0;
        }
        $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);

        // Getting grouped data form RAW
        $aggregatedData = $this->mysql->dailyRawMetrics->getAggregatedDataByRange($time, $startId, $maxId);
        if (!count($aggregatedData)) {
            $this->out('No raw data in dailyRawMetrics from startId ' . $startId);
            sleep(5);
            return 0;
        }


        // Saving into aggr table
        $saved = 0;
        foreach ($aggregatedData as $row) {
            $res = false;
            try {
                $row['minute'] = date('H') * 60 + date('i');
                $res = $this->mysql->dailyMetrics->insert($row);
            } catch (\Exception $e) {
                $this->out($e->getMessage());
            }

            if ($res) {
                $saved++;
            }
        }
        return $saved;
    }
}