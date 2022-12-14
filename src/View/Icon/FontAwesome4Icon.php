<?php

namespace Tools\View\Icon;

use Cake\View\StringTemplate;
use Tools\View\Icon\Collector\FontAwesome4IconCollector;

class FontAwesome4Icon implements IconInterface {

	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected $template;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span class="{{class}}"{{attributes}}></span>',
		];

		$this->template = new StringTemplate(['icon' => $config['template']]);
	}

	/**
	 * @param string $path
	 *
	 * @return array<string>
	 */
	public function names(string $path): array {
		return FontAwesome4IconCollector::collect($path);
	}

	/**
	 * @param string $icon
	 * @param array $options
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function render(string $icon, array $options = [], array $attributes = []): string {
		$formatOptions = $attributes + [
		];

		$namespace = 'fa';

		$class = [
			$namespace,
		];
		if (!empty($options['extra'])) {
			foreach ($options['extra'] as $i) {
				$class[] = $namespace . '-' . $i;
			}
		}
		if (!empty($options['size'])) {
			$class[] = $namespace . '-' . ($options['size'] === 'large' ? 'large' : $options['size'] . 'x');
		}
		if (!empty($options['pull'])) {
			$class[] = 'pull-' . $options['pull'];
		}
		if (!empty($options['rotate'])) {
			$class[] = $namespace . '-rotate-' . (int)$options['rotate'];
		}
		if (!empty($options['spin'])) {
			$class[] = $namespace . '-spin';
		}

		$options['class'] = implode(' ', $class) . ' ' . $namespace . '-' . $icon;
		if (!empty($attributes['class'])) {
			$options['class'] .= ' ' . $attributes['class'];
		}
		$options['attributes'] = $this->template->formatAttributes($formatOptions, ['class']);

		return $this->template->format('icon', $options);
	}

}
