<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

class RunDecisionCommand extends Command
{
	// DATA TABLE ROW NAMES
	const COUNTRIES = 'Countries';
	const STATUS = 'Status';
	const STATUS_DETAILS = 'Status Details';

	// FLIGHT STATUS TYPES
	const FLIGHT_STATUS_CANCEL = 'Cancel';
	const FLIGHT_STATUS_DELAY = 'Delay';

	// DESIGION PERIODS
	const CANCELED_FLIGHT_PERIOD_IN_DAYS = 14;
	const DELAYED_FLIGHT_PERIOD_IN_HOURS = 3;

	const EU_COUNTRIES = [
		'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL',
		'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV',
		'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
	];

	protected static $defaultName = 'app:run-decision';

	public function __construct($projectDir)
	{
		$this->projectDir = $projectDir;

		parent::__construct();
	}

	protected function configure()
	{
		$this->setDescription('Decide if flights are claimable');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$rows = $this->getCsvRowsAsArrays();

		foreach ($rows as $row) {
			$claimable = false;

			if (in_array($row[self::COUNTRIES], self::EU_COUNTRIES)) {
				switch ($row[self::STATUS]) {
					case self::FLIGHT_STATUS_CANCEL:
						if ($row[self::STATUS_DETAILS] <= self::CANCELED_FLIGHT_PERIOD_IN_DAYS) {
							$claimable = true;
						}

						break;
					case self::FLIGHT_STATUS_DELAY:
						if ($row[self::STATUS_DETAILS] >= self::DELAYED_FLIGHT_PERIOD_IN_HOURS) {
							$claimable = true;
						}

						break;
				}
			}

			$output->writeln(sprintf('%s %s %s %s', $row[self::COUNTRIES], $row[self::STATUS], $row[self::STATUS_DETAILS], $claimable ? 'Y' : 'N'));
		}

		return 1;
	}

	private function getCsvRowsAsArrays()
	{
		$inputFile = $this->projectDir . '/public/data.csv';

		$decoder = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
		
		return $decoder->decode(file_get_contents($inputFile), 'csv');
	}
}