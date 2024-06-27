<?php
namespace LPC\LpcBase\Update;

abstract class AbstractUpdate
	implements
		\TYPO3\CMS\Install\Updates\UpgradeWizardInterface,
		\TYPO3\CMS\Install\Updates\ConfirmableInterface,
		\TYPO3\CMS\Install\Updates\ChattyInterface
{
	/**
	 * @var boolean
	 *
	 * if true, the first 100 steps are simulated and the actions taken are outputted.
	 * WARNING: any direct writes (db, filesystem ...) that is done in a derived job will be executed
	 * normally. You do a check on this variable and output something meaningful instead.
	 */
	protected $debug = false;

	protected $jobs = [];

	protected $currentJob = null;

	protected $output;

	protected $hasError = false;

	public function setOutput(\Symfony\Component\Console\Output\OutputInterface $output): void {
		$this->output = $output;
	}

	public function updateNecessary(): bool {
		foreach($this->jobs as $job) {
			if($job->check()) {
				return true;
			}
		}
		return false;
	}

	public function executeUpdate(): bool {
		$steps = [];

		try {
			foreach($this->jobs as $i => $job) {
				if($job->check()) {
					$this->currentJob = $job;
					$steps = array_merge($steps,$job->prepare($this->output));
				}
			}
			$this->currentJob = null;
		} catch(UpdateException $ex) {
			$this->output->writeln($ex->getMessage());
			$this->hasError = true;
			if($ex->failsUpdate()) {
				return false;
			}
		}

		// @extensionScannerIgnoreLine
		if($this->debug) {
			$limit = 100;
		}

		$placeholders = [];
		foreach($steps as $step) {
			// @extensionScannerIgnoreLine
			if($this->debug) {
				var_dump($step);
				if(--$limit <= 0) {
					break;
				}
			} else {
				$step->execute($placeholders);
			}
		}

		return !$this->hasError && !$this->debug;
	}

	public function getPrerequisites(): array {
		return [
			\TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite::class
		];
	}

	public function registerJob(AbstractJob $job) {
		$this->jobs[] = $job;
	}

	public function getDescription(): string {
		return implode("\n", array_map(function($job) {
			return $job->getDescription();
		}, $this->jobs));
	}

	public function getConfirmation(): \TYPO3\CMS\Install\Updates\Confirmation {
		return new \TYPO3\CMS\Install\Updates\Confirmation(
			'Go ahead?',
			"This will: \n".implode("\n", array_map(function($job) {
				return $job->getLabel();
			}, array_filter($this->jobs, function($job) {
				return $job->check();
			}))),
			true
		);
	}

	protected function copyFileReferences($newFieldname,$newTable = null,$oldFieldname = null,$oldTable = null,$parentStep = null) {
		return $this->currentJob->copyFileReferences($newFieldname,$newTable,$oldFieldname,$oldTable,$parentStep);
	}

	protected function createFileReference($pathPrefix,$fieldname,$table = null,$oldFieldname = null) {
		return $this->currentJob->createFileReference($pathPrefix,$fieldname,$table,$oldFieldname);
	}

	/**
	 * add an error message
	 *
	 * @param string $message
	 * @param mixed $fail can be
	 *    'step' to indicate that the current step (one plugin, domain object, ...) can not be executed
	 *    true to fail the whole update process
	 *    false to add an error but allow the step to complete anyway
	 */
	protected function addError($message,$fail = false) {
		$this->hasError = true;
		if($fail) {
			throw new UpdateException($message,$fail !== false && $fail !== 'step');
		} else if($this->output) {
			$this->output->writeln($message);
		}
	}

	protected function addSubstep(UpdateStep $step) {
		$this->currentJob->addSubstep($step);
	}

	protected function getCurrentUid() {
		return $this->currentJob->getCurrentUid();
	}

	protected function getCurrentStep() {
		return $this->currentJob->getCurrentStep();
	}

	protected function getCurrentJob() {
		return $this->currentJob;
	}

	protected function getCurrentRow() {
		return $this->currentJob->getCurrentRow();
	}
}
