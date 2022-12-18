<?php

namespace ADT\ApiJsonRouter;

class ApiDocumentation
{
	protected ?string $documentation = null;

	private string $title;

	protected array $calls;

	public function __construct(string $title, array $calls)
	{
		$this->title = $title;
		foreach ($calls as $call) {
			$this->addCall($call);
		}
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
	public function addCall(array $call): self
	{
		$this->calls[] = $call;
		return $this;
	}

	public function createDocumentation(): void
	{
		$this->documentation = '# ' . $this->title . "\n\n";
		$first = true;
		foreach ($this->calls as $call) {
			if ($first) {
				$first = false;
			} else {
				$this->documentation .= "\n\n";
			}
			$this->documentCall($call);
		}
		$this->documentation .= "\n";
	}

	public function getDocumentation(bool $recreate = false): string
	{
		if ($recreate || $this->documentation === null) {
			$this->createDocumentation();
		}
		return $this->documentation;
	}

	protected function documentCall(array $call)
	{
		$this->documentation .= '## ' . $call['title'] . "\n\n";
		$this->documentation .= $call['description'] . "\n\n";
		$this->documentation .= "**URL**: `" . $call['path'] . "`\n\n";
		$this->documentation .= "**Method**: " . $call['method'];

		if (!empty($call['parameters'])) {
			$this->documentation .= "\n\n**Parameters**:\n\n```json\n" . json_encode($call['parameters'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
		}

		if (!empty($call['body'])) {
			$this->documentation .= "\n\n**Body**:\n\n```json\n" . json_encode($call['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
		}

		if (!empty($call['response'])) {
			$this->documentation .= "\n\n**Response**:\n\n```json\n" . json_encode($call['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
		}
	}
}
