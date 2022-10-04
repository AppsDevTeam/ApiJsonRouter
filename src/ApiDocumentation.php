<?php

namespace ADT\ApiJsonRouter;

class ApiDocumentation {

	/** @var string|null */
	protected $documentation = NULL;

	/** @var array */
	protected $calls = [];

	/** @var int */
	private $titleLevel = 2;

	public function __construct(?array $calls) {
		foreach ($calls as $call) {
			$this->addCall($call);
		}
	}

	public function setTitleLevel(int $titleLevel): self {
		$this->titleLevel = $titleLevel;
		return $this;
	}

	/**
	 * @param array $call an array with following key values
	 * [
	 *     'path' => 'path/to/call',
	 *     'method' => 'GET/POST/PUT/...',
	 *     'description' => 'Human readable description of the call',
	 *     'title' => 'Title',
	 *     'body' => NULL || JSON Schema as parsed by json_decode
	 * ]
	 * @return $this
	 */
	public function addCall(array $call): self {
		$this->calls[] = $call;
		return $this;
	}

	protected function documentCall($call) {
		$this->documentation .= str_repeat('#', $this->titleLevel) . ' ' . $call['title'] . "\n\n";
		$this->documentation .= $call['description'] . "\n\n";
		$this->documentation .= "**URL**: `" . $call['path'] . "`\n\n";
		$this->documentation .= "**Method**: " . $call['method'];

		if (isset($call['query'])) {
			$this->documentation .= "\n\n**Query**:\n\n```json\n" . json_encode($call['query'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
		}

		if ($call['body'] !== NULL) {
			$this->documentation .= "\n\n**Body**:\n\n```json\n" . json_encode($call['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
		}

		if (isset($call['response'])) {
			$this->documentation .= "\n\n**Response**:\n\n```json\n" . json_encode($call['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
		}

	}

	public function createDocumentation(): void {
		$this->documentation = '';
		$first = TRUE;
		foreach ($this->calls as $call) {
			if ($first) {
				$first = FALSE;
			} else {
				$this->documentation .= "\n\n";
			}
			$this->documentCall($call);
		}
		$this->documentation .= "\n";
	}

	public function getDocumentation($recreate = FALSE): string {
		if ($recreate || $this->documentation === NULL) {
			$this->createDocumentation();
		}
		return $this->documentation;
	}
}