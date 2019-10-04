<?php

namespace Timber\Factory;

use Timber\CoreInterface;
use Timber\Term;

use WP_Term;
use WP_Term_Query;

/**
 * Internal API class for instantiating Terms
 */
class TermFactory {
	public function from($params) {
		if (is_int($params)) {
			return $this->from_id($params);
		}

		if (is_object($params)) {
			return $this->from_obj($params);
		}

		if ($this->is_numeric_array($params)) {
			return array_map([$this, 'from'], $params);
		}

		if (is_array($params)) {
			return $this->from_obj(new WP_Term_Query($params));
		}
	}

	protected function from_id(int $id) {
		return $this->build(get_term($id));
	}

	protected function get_term_class(WP_Term $term) : string {
		// Get the user-configured Class Map
		$map = apply_filters( 'timber/term/classmap', [
			'post_tag' => Term::class,
			'category' => Term::class,
		]);

		$class = $map[$term->taxonomy] ?? null;

		if (is_callable($class)) {
			$class = $class($term);
		}

    // If we don't have a term class by now, fallback on the default class
		return $class ?? Term::class;
	}

	protected function from_obj(object $obj) {
		if ($obj instanceof CoreInterface) {
			// We already have a Timber Core object of some kind
			return $obj;
		}

		if ($obj instanceof WP_Term) {
			return $this->build($obj);
		}

		if ($obj instanceof WP_Term_Query) {
			return array_map([$this, 'build'], $obj->get_terms());
		}

		throw new \InvalidArgumentException(sprintf(
			'Expected an instance of Timber\CoreInterface or WP_User, got %s',
			get_class($obj)
		));
	}

	protected function build(WP_Term $term) : CoreInterface {
		$class = $this->get_term_class($term);

    // @todo make Core constructors protected, call Term::build() here
		return new $class($term);
	}

	protected function is_numeric_array(array $arr) {
		foreach (array_keys($arr) as $k) {
			if ( ! is_int($k) ) return false;
		}
		return true;
	}
}