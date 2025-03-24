<?php

namespace PortableInfobox\Services\Helpers;

use Exception;

class InfoboxParamsValidator {

	private static $supportedParams = [
		'accent-color-default',
		'accent-color-source',
		'accent-color-text-default',
		'accent-color-text-source',
		'layout',
		'theme',
		'theme-source',
		'type',
		'name'
	];

	private static $supportedLayouts = [
		'default',
		'stacked'
	];

	private static $colorNames = [
		'aliceblue',
		'antiquewhite',
		'aqua',
		'aquamarine',
		'azure',
		'beige',
		'bisque',
		'black',
		'blanchedalmond',
		'blue',
		'blueviolet',
		'brown',
		'burlywood',
		'cadetblue',
		'chartreuse',
		'chocolate',
		'coral',
		'cornflowerblue',
		'cornsilk',
		'crimson',
		'cyan',
		'darkblue',
		'darkcyan',
		'darkgoldenrod',
		'darkgray',
		'darkgrey',
		'darkgreen',
		'darkkhaki',
		'darkmagenta',
		'darkolivegreen',
		'darkorange',
		'darkorchid',
		'darkred',
		'darksalmon',
		'darkseagreen',
		'darkslateblue',
		'darkslategray',
		'darkslategrey',
		'darkturquoise',
		'darkviolet',
		'deeppink',
		'deepskyblue',
		'dimgray',
		'dimgrey',
		'dodgerblue',
		'firebrick',
		'floralwhite',
		'forestgreen',
		'fuchsia',
		'gainsboro',
		'ghostwhite',
		'gold',
		'goldenrod',
		'gray',
		'grey',
		'green',
		'greenyellow',
		'honeydew',
		'hotpink',
		'indianred',
		'indigo',
		'ivory',
		'khaki',
		'lavender',
		'lavenderblush',
		'lawngreen',
		'lemonchiffon',
		'lightblue',
		'lightcoral',
		'lightcyan',
		'lightgoldenrodyellow',
		'lightgray',
		'lightgrey',
		'lightgreen',
		'lightpink',
		'lightsalmon',
		'lightseagreen',
		'lightskyblue',
		'lightslategray',
		'lightslategrey',
		'lightsteelblue',
		'lightyellow',
		'lime',
		'limegreen',
		'linen',
		'magenta',
		'maroon',
		'mediumaquamarine',
		'mediumblue',
		'mediumorchid',
		'mediumpurple',
		'mediumseagreen',
		'mediumslateblue',
		'mediumspringgreen',
		'mediumturquoise',
		'mediumvioletred',
		'midnightblue',
		'mintcream',
		'mistyrose',
		'moccasin',
		'navajowhite',
		'navy',
		'oldlace',
		'olive',
		'olivedrab',
		'orange',
		'orangered',
		'orchid',
		'palegoldenrod',
		'palegreen',
		'paleturquoise',
		'palevioletred',
		'papayawhip',
		'peachpuff',
		'peru',
		'pink',
		'plum',
		'powderblue',
		'purple',
		'red',
		'rosybrown',
		'royalblue',
		'saddlebrown',
		'salmon',
		'sandybrown',
		'seagreen',
		'seashell',
		'sienna',
		'silver',
		'skyblue',
		'slateblue',
		'slategray',
		'slategrey',
		'snow',
		'springgreen',
		'steelblue',
		'tan',
		'teal',
		'thistle',
		'tomato',
		'turquoise',
		'violet',
		'wheat',
		'white',
		'whitesmoke',
		'yellow',
		'yellowgreen'
	];
	private static $colorNamesFlipped;

	private const REGEX_PERCENT = '(?:100|\d{1,2})%';
	private const REGEX_HUE = '-?(?:3(?:60|[0-5]\d)|[12]?\d{1,2})';
	private const REGEX_ALPHAVAL = '(?:' . self::REGEX_PERCENT . '|[01]?\.\d+|[01])';
	private const REGEX_RGBVAL = '(?:' . self::REGEX_PERCENT . '|2(?:5[0-5]|[0-4]\d)|1?\d{1,2})';
	private const REGEX_HEXRGB = '/^#?[a-f0-9]{3}(?:[a-f0-9]{3}(?:[a-f0-9]{2})?|[a-f0-9])?$/';
	private const REGEX_RGB = '/^rgb\((?:' . self::REGEX_RGBVAL . ',){2}' . self::REGEX_RGBVAL . '\)$/';
	private const REGEX_RGBA = '/^rgba\((?:' . self::REGEX_RGBVAL . ',){3}' . self::REGEX_ALPHAVAL . '\)$/';
	private const REGEX_HSL = '/^hsl\(' . self::REGEX_HUE . ',' . self::REGEX_PERCENT . ',' .
		self::REGEX_PERCENT . '\)$/';
	private const REGEX_HSLA = '/^hsla\(' . self::REGEX_HUE . ',(?:' . self::REGEX_PERCENT . ',){2}' .
		self::REGEX_ALPHAVAL . '\)$/';

	public function __construct() {
		if ( self::$colorNamesFlipped === null ) {
			self::$colorNamesFlipped = array_flip( self::$colorNames );
		}
	}

	/**
	 * validates infobox tags attribute names
	 * @param array $params
	 * @throws InvalidInfoboxParamsException
	 * @todo consider using hashmap instead of array ones validator grows
	 * @return bool
	 */
	public function validateParams( $params ) {
		foreach ( array_keys( $params ) as $param ) {
			if ( !in_array( $param, self::$supportedParams ) ) {
				throw new InvalidInfoboxParamsException( $param );
			}
		}

		return true;
	}

	/**
	 * validates if argument is valid color value.
	 * @param string $color
	 * @return string
	 */
	public function validateColorValue( $color ) {
		$color = strtolower( preg_replace( '/\s+/', '', $color ) );

		if ( preg_match( self::REGEX_HEXRGB, $color ) ) {
			return substr( $color, 0, 1 ) === '#' ? $color : '#' . $color;
		}

		if ( isset( self::$colorNamesFlipped[$color] ) ||
			preg_match( self::REGEX_RGB, $color ) ||
			preg_match( self::REGEX_RGBA, $color ) ||
			preg_match( self::REGEX_HSL, $color ) ||
			preg_match( self::REGEX_HSLA, $color )
		) {
			return $color;
		}

		return '';
	}

	/**
	 * checks if given layout name is supported
	 * @param string $layoutName
	 * @return bool
	 */
	public function validateLayout( $layoutName ) {
		return $layoutName && in_array( $layoutName, self::$supportedLayouts );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class InvalidInfoboxParamsException extends Exception {
}
