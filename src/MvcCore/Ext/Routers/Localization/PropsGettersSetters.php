<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Routers\Localization;

/**
 * @mixin \MvcCore\Ext\Routers\Localization
 */
trait PropsGettersSetters {

	/***************************************************************************
	 *                         Configurable Properties                         *
	 **************************************************************************/

	/**
	 * Default language and locale. Language is always defined as two lower case 
	 * characters - international language code and locale is always defined as
	 * two or three upper case characters or digits - international locale code.
	 * Default localization is used in cases, when is not possible to detect 
	 * language and locale from URL or when is not possible to detect language 
	 * and locale from `Accept-Language` http header or not possible to get 
	 * previous localization from session.
	 * @var \string[]
	 */
	protected $defaultLocalization = [];

	/**
	 * Current router context localization value. It could contain in first index
	 * international language code string and nothing more or the language under
	 * first index and international locale code under second index.
	 * If there are no language and locale detected, array is empty.
	 * @var \string[]
	 */
	protected $localization = [];

	/**
	 * If `TRUE`, redirect first request by session to default localization 
	 * version if localization in request is not allowed.
	 * If not configured, `FALSE` by default to not redirect in first request to
	 * default localization version but to route requested localization version.
	 * @var bool
	 */
	protected $redirectFirstRequestToDefault = FALSE;

	/**
	 * `TRUE` by default to allow routing with non-localized routes.
	 * If `FALSE` non-localized routes are ignored and there is thrown an 
	 * exception in development environment.
	 * @var bool
	 */
	protected $allowNonLocalizedRoutes = TRUE;

	/**
	 * Detect localization only by language record from `Accept-Language` http 
	 * header record, not together with locale code. Parsed international 
	 * language code will be enough to choose final target application 
	 * localization. It will be chosen first localization in allowed list with 
	 * detected language. `TRUE` by default. If `FALSE`, then there is necessary 
	 * to send into application in `Accept-Language` http header international 
	 * language code together with international locale code with the only same 
	 * combination which application has configured in allowed localizations only.
	 * @var bool
	 */
	protected $detectLocalizationOnlyByLang = TRUE;

	/**
	 * List of allowed localization strings in your application, default 
	 * localization will be allowed automatically. Define this list as array of 
	 * strings. Every item has to be international language code or it has to be
	 * international language code and international locale code separated by
	 * dash.
	 * @var array
	 */
	protected $allowedLocalizations = [];

	/**
	 * List of localization equivalents used in localization detection by http
	 * header `Accept-Language` parsed in first request. It could be used for 
	 * language very similar countries like Ukraine & Russia, Czech & Slovakia ...
	 * Keys in this array is target localization, value is an array with target 
	 * localization equivalents.
	 * @var array
	 */
	protected $localizationEquivalents = [];

	/**
	 * If `TRUE` (default `FALSE`), route records like `pattern`, `match`, 
	 * `reverse` or `defaults` has to be defined by international language code 
	 * and international locale code, not only by language code by default.
	 * This option is very rare, if different locales have different naming 
	 * for URL strings.
	 * @var bool
	 */
	protected $routeRecordsByLanguageAndLocale = FALSE;


	/***************************************************************************
	 *                           Internal Properties                           *
	 **************************************************************************/

	/**
	 * Localized route class name, never patched in application core, 
	 * only used internally in this class.
	 * @var string
	 */
	protected static $routeClassLocalized = '\MvcCore\Ext\Routers\Localizations\Route';

	/**
	 * Localization founded in session, parsed from previous requests.
	 * @var \string[]|NULL
	 */
	protected $sessionLocalization = NULL;
	
	/**
	 * Localization founded in request.
	 * @var \string[]|NULL
	 */
	protected $requestLocalization = NULL;
	
	/**
	 * Localization equivalent founded in request.
	 * @var \string[]|NULL
	 */
	protected $requestLocalizationEquivalent = NULL;

	/**
	 * Localization value in specially named `$_GET` param (if founded) 
	 * for strict session mode localization switching.
	 * @var string
	 */
	protected $switchUriParamLocalization = NULL;

	/**
	 * If `NULL`, request wasn't first, there was something in session stored by previous requests.
	 * If `TRUE` or `FALSE`, request is first, nothing is in session yet and `TRUE` means
	 * the best localization match by sent http headers (`Accept-Language`).
	 * `FALSE` then means that there was a match, but it could be a lower prioritized
	 * language and locale from `Accept-Language` or it could be default application localization.
	 * @var bool|NULL
	 */
	protected $firstRequestLocalizationDetection = NULL;

	/**
	 * Default localization, imploded from array to string.
	 * @var string|NULL
	 */
	protected $defaultLocalizationStr = NULL;

	/**
	 * Original request path before localization manipulation.
	 * @var string|NULL
	 */
	protected $originalRequestPath = NULL;
	

	/***************************************************************************
	 *                             Public Methods                              *
	 **************************************************************************/
	
	/**
	 * Get default language and locale. Language is always defined as two lower case 
	 * characters - international language code and locale is always defined as
	 * two or three upper case characters or digits - international locale code.
	 * Default localization is used in cases, when is not possible to detect 
	 * language and locale from URL or when is not possible to detect language 
	 * and locale from `Accept-Language` http header or not possible to get 
	 * previous localization from session.
	 * @param bool $asString `FALSE` by default to get array with lang and locale, 
	 *						 `TRUE` to get lang and locale as string.
	 * @return string|\string[]
	 */
	public function GetDefaultLocalization ($asString = FALSE) {
		return $asString
			? implode(static::LANG_AND_LOCALE_SEPARATOR, $this->defaultLocalization)
			: $this->defaultLocalization;
	}
	
	/**
	 * Set default language and locale. Language has to be defined as two lower case 
	 * characters - international language code and locale has to be defined as
	 * two or three upper case characters or digits - international locale code.
	 * Default localization is used in cases, when is not possible to detect 
	 * language and locale from URL or when is not possible to detect language 
	 * and locale from `Accept-Language` http header or not possible to get 
	 * previous localization from session.
	 * @var string $defaultLocalizationOrLanguage It could be `en` or `en-US`, `en-GB`...
	 * @var string $defaultLocale It could be `US`, `GB`...
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetDefaultLocalization ($defaultLocalizationOrLanguage, $defaultLocale = NULL) {
		if ($defaultLocalizationOrLanguage === NULL) 
			throw new \InvalidArgumentException("[".get_class($this)."] Default localization must be defined at least by the language.");
		if ($defaultLocale === NULL) {
			$delimiterPos = strpos($defaultLocalizationOrLanguage, static::LANG_AND_LOCALE_SEPARATOR);
			if ($delimiterPos !== FALSE) {
				$defaultLocale = substr($defaultLocalizationOrLanguage, $delimiterPos + 1);
				$defaultLocalizationOrLanguage = substr($defaultLocalizationOrLanguage, 0, $delimiterPos);
			}
			if (strlen($defaultLocale) > 0) {
				$this->defaultLocalization = [$defaultLocalizationOrLanguage, $defaultLocale];
			} else {
				$this->defaultLocalization = [$defaultLocalizationOrLanguage];
			}
		} else {
			$this->defaultLocalization = [$defaultLocalizationOrLanguage, $defaultLocale];
		}
		return $this;
	}

	/**
	 * Get current router context localization value. It could contain in first 
	 * index international language code string and nothing more or the language 
	 * under first index and international locale code under second index.
	 * If there are no language and locale detected, returned array is empty.
	 * @param bool $asString `FALSE` by default to get array with lang and locale, 
	 *						 `TRUE` to get lang and locale as string.
	 * @return string|\string[]
	 */
	public function GetLocalization ($asString = FALSE) {
		return $asString
			? implode(static::LANG_AND_LOCALE_SEPARATOR, $this->localization ?: $this->defaultLocalization)
			: ($this->localization ?: $this->defaultLocalization);
	}

	/**
	 * Set current router context localization value. It could contain in first 
	 * index international language code string and nothing more or the language 
	 * under first index and international locale code under second index.
	 * @param string $lang 
	 * @param string $locale 
	 * @throws \InvalidArgumentException Localization must be defined at least by the language.
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetLocalization ($lang, $locale = NULL) {
		if ($lang === NULL) throw new \InvalidArgumentException(
			"[".get_class($this)."] Localization must be defined at least by the language."
		);
		$this->localization[0] = $lang;
		if ($locale !== NULL) $this->localization[1] = $locale;
		return $this;
	}

	/**
	 * If `TRUE`, redirect first request by session to default localization 
	 * version if localization in request is not allowed.
	 * If not configured, `FALSE` by default to not redirect in first request to
	 * default localization version but to route requested localization version.
	 * @return boolean
	 */
	public function GetRedirectFirstRequestToDefault () {
		return $this->redirectFirstRequestToDefault;
	}

	/**
	 * If `TRUE`, redirect first request by session to default localization 
	 * version if localization in request is not allowed.
	 * If not configured, `FALSE` by default to not redirect in first request to
	 * default localization version but to route requested localization version.
	 * @param bool $redirectFirstRequestToDefault
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetRedirectFirstRequestToDefault ($redirectFirstRequestToDefault = TRUE) {
		$this->redirectFirstRequestToDefault = $redirectFirstRequestToDefault;
		return $this;
	}

	/**
	 * `TRUE` by default to allow routing with non-localized routes.
	 * If `FALSE` non-localized routes are ignored and there is thrown an 
	 * exception in development environment.
	 * @return bool
	 */
	public function GetAllowNonLocalizedRoutes () {
		return $this->allowNonLocalizedRoutes;
	}

	/**
	 * `TRUE` by default to allow routing with non-localized routes.
	 * If `FALSE` non-localized routes are ignored and there is thrown an 
	 * exception in development environment.
	 * @param bool $allowNonLocalizedRoutes
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetAllowNonLocalizedRoutes ($allowNonLocalizedRoutes = TRUE) {
		$this->allowNonLocalizedRoutes = $allowNonLocalizedRoutes;
		return $this;
	}

	/**
	 * Get detect localization only by language record from `Accept-Language` http 
	 * header record, not together with locale code. Parsed international 
	 * language code will be enough to choose final target application 
	 * localization. It will be chosen first localization in allowed list with 
	 * detected language. `TRUE` by default. If `FALSE`, then there is necessary 
	 * to send into application in `Accept-Language` http header international 
	 * language code together with international locale code with the only same 
	 * combination which application has configured in allowed localizations only.
	 * @return bool
	 */
	public function GetDetectLocalizationOnlyByLang () {
		return $this->detectLocalizationOnlyByLang;
	}

	/**
	 * Set detect localization only by language from `Accept-Language` http 
	 * header record, not together with locale code. Parsed international 
	 * language code will be enough to choose final target application 
	 * localization. It will be chosen first localization in allowed list with 
	 * detected language. `TRUE` by default. If `FALSE`, then there is necessary 
	 * to send into application in `Accept-Language` http header international 
	 * language code together with international locale code with the only same 
	 * combination which application has configured in allowed localizations only.
	 * @param bool $detectLocalizationOnlyByLang
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetDetectLocalizationOnlyByLang ($detectLocalizationOnlyByLang = TRUE) {
		$this->detectLocalizationOnlyByLang = $detectLocalizationOnlyByLang;
		return $this;
	}

	/**
	 * Get list of allowed localization strings in your application, default 
	 * localization will be allowed automatically. List is returned as array of 
	 * strings. Every item has to be international language code or it has to be
	 * international language code and international locale code separated by
	 * dash.
	 * @return array
	 */
	public function GetAllowedLocalizations () {
		return array_values($this->allowedLocalizations);
	}

	/**
	 * Set list of allowed localization strings in your application, default 
	 * localization will be allowed automatically. List has to be defined as array 
	 * of strings. Every item has to be international language code or it has to be
	 * international language code and international locale code separated by
	 * dash. All previously defined allowed localizations will be replaced.
	 * Default localization is always allowed automatically.
	 * @var string $allowedLocalizations...,	International lower case language 
	 *											code(s) (+ optionally dash character 
	 *											+ upper case international locale 
	 *											code(s)).
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetAllowedLocalizations ($allowedLocalizations) {
		$allowedLocalizations = func_get_args();
		if (count($allowedLocalizations) === 1 && is_array($allowedLocalizations[0])) 
			$allowedLocalizations = $allowedLocalizations[0];
		$this->allowedLocalizations = array_combine($allowedLocalizations, $allowedLocalizations);
		return $this;
	}

	/**
	 * Add list of allowed localization strings in your application, default 
	 * localization will be allowed automatically. List has to be defined as array 
	 * of strings. Every item has to be international language code or it has to be
	 * international language code and international locale code separated by
	 * dash. 
	 * Default localization is always allowed automatically.
	 * @var string $allowedLocalizations...,	International lower case language 
	 *											code(s) (+ optionally dash character 
	 *											+ upper case international locale 
	 *											code(s)).
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function AddAllowedLocalizations ($allowedLocalizations) {
		$allowedLocalizations = func_get_args();
		if (count($allowedLocalizations) === 1 && is_array($allowedLocalizations[0])) 
			$allowedLocalizations = $allowedLocalizations[0];
		$this->allowedLocalizations = array_merge(
			$this->allowedLocalizations,
			array_combine($allowedLocalizations, $allowedLocalizations)
		);
		/*foreach ($allowedLocalizations as $allowedLocalization) 
			$this->allowedLocalizations[$allowedLocalization] = $allowedLocalization;*/
		return $this;
	}
	
	/**
	 * Get list of localization equivalents used in localization detection by http
	 * header `Accept-Language` parsed in first request. It could be used for 
	 * language very similar countries like Ukraine & Russia, Czech & Slovakia ...
	 * Keys in this array is target localization, value is an array with target 
	 * localization equivalents.
	 * @return array
	 */
	public function GetLocalizationEquivalents () {
		return $this->localizationEquivalents;
	}

	/**
	 * Set list of localization equivalents used in localization detection by http
	 * header `Accept-Language` parsed in first request. It could be used for 
	 * language very similar countries like Ukraine & Russia, Czech & Slovakia ...
	 * Keys in this array is target localization, value is an array with target 
	 * localization equivalents. All previously configured localization equivalents
	 * will be replaced with given configuration.
	 * @param array $localizationEquivalents	Keys in this array is target 
	 *											localization, value is an array 
	 *											with target localization equivalents.
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetLocalizationEquivalents (array $localizationEquivalents = []) {
		$this->localizationEquivalents = [];
		$this->AddLocalizationEquivalents($localizationEquivalents);
		return $this;
	}
	
	/**
	 * Add or merge items in list with localization equivalents used in localization 
	 * detection by http header `Accept-Language` parsed in first request. It could 
	 * be used for language very similar countries like Ukraine & Russia, Czech & Slovakia ...
	 * Keys in this array is target localization, value is an array with target 
	 * localization equivalents. All previously configured localization equivalents
	 * will be merged with given configuration.
	 * @param array $localizationEquivalents	Keys in this array is target 
	 *											localization, value is an array 
	 *											with target localization equivalents.
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function AddLocalizationEquivalents (array $localizationEquivalents = []) {
		foreach ($localizationEquivalents as $targetLocalization => $targetLocalizationEquivalents) {
			foreach ($targetLocalizationEquivalents as $targetLocalizationEquivalent) 
				$this->localizationEquivalents[$targetLocalizationEquivalent] = $targetLocalization;
		}
		return $this;
	}

	/**
	 * If `TRUE` (default `FALSE`), route records like `pattern`, `match`, 
	 * `reverse` or `defaults` has to be defined by international language code 
	 * and international locale code, not only by language code by default.
	 * This option is very rare, if different locales have different naming 
	 * for URL strings.
	 * @return bool
	 */
	public function GetRouteRecordsByLanguageAndLocale () {
		return $this->routeRecordsByLanguageAndLocale;
	}

	/**
	 * If `TRUE` (default `FALSE`), route records like `pattern`, `match`, 
	 * `reverse` or `defaults` has to be defined by international language code 
	 * and international locale code, not only by language code by default.
	 * This option is very rare, if different locales have different naming 
	 * for URL strings.
	 * @param bool $routeRecordsByLanguageAndLocale
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetRouteRecordsByLanguageAndLocale ($routeRecordsByLanguageAndLocale = TRUE) {
		$this->routeRecordsByLanguageAndLocale = $routeRecordsByLanguageAndLocale;
		return $this;
	}

	/**
	 * Append or prepend new request routes.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	], "eshop");`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		'products_list'	=> [
	 *			"pattern"			=>  [
	 *				"en"				=> "/products-list/<name>/<color>",
	 *				"de"				=> "/produkt-liste/<name>/<color>"
	 *			],
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		]
	 *	], ["en" => "eshop", "de" => "einkaufen"]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
	 * @param \MvcCore\Ext\Routers\Localizations\Route[]|array $routes 
	 *				Keyed array with routes, keys are route names or route
	 *				`Controller::Action` definitions.
	 * @param string|array|NULL $groupNames 
	 *				Group name or names is first matched/parsed word(s) in 
	 *				requested path to group routes by to try to match only routes 
	 *				you really need, not all of them. If `NULL` by default, routes 
	 *				are inserted into default group. If argument is an array, it 
	 *				must contain localization keys and localized group names.
	 * @param bool $prepend	
	 *				Optional, if `TRUE`, all given routes will be prepended from 
	 *				the last to the first in given list, not appended.
	 * @param bool $throwExceptionForDuplication 
	 *				`TRUE` by default. Throw an exception, if route `name` or 
	 *				route `Controller:Action` has been defined already. If 
	 *				`FALSE` old route is overwritten by new one.
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function AddRoutes (array $routes = [], $groupNames = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		$routeClass = self::$routeClass;
		self::$routeClass = self::$routeClassLocalized;
		parent::AddRoutes($routes, $groupNames, $prepend, $throwExceptionForDuplication);
		self::$routeClass = $routeClass;
		return $this;
	}

	/**
	 * Add route instance into named routes group. Every routes group is chosen 
	 * in routing moment by first parsed word from requested URL.
	 * @param \MvcCore\Ext\Routers\Localizations\Route $route 
	 *		  Localized route instance.
	 * @param string $routeName 
	 *		  A route instance name.
	 * @param string|\string[]|NULL $groupNames 
	 *		  Group name or list of group names to assign given route instance into.
	 * @param bool $prepend 
	 *		  If `TRUE`, prepend route instance in final group or not.
	 * @throws \InvalidArgumentException 
	 *		  Localized routes group cannot contain non-localized route instance.
	 * @return void
	 */
	protected function addRouteToGroup (\MvcCore\IRoute $route, $routeName, $groupNames, $prepend) {
		$routesGroupsKeys = [];
		if ($groupNames === NULL) {
			$routesGroupsKeys[] = '';
		} else if (is_string($groupNames)) {
			$routesGroupsKeys[] = $groupNames;
			$route->SetGroupName($groupNames);
		} else if (is_array($groupNames)) {
			foreach ($groupNames as $routeLocalizationKey => $groupName)
				$routesGroupsKeys[] = $routeLocalizationKey . '/' . $groupName;
			if ($route instanceof \MvcCore\Ext\Routers\Localizations\Route) {
				$route->SetGroupName($groupNames);
			} else {
				throw new \InvalidArgumentException (
					"[".get_class($this)."] Localized routes group cannot contain non-localized route instance. "
					. "(group names: ".json_encode($groupNames).", route: {$route})"
				);
			}
		}
		foreach ($routesGroupsKeys as $routesGroupsKey) {
			if (array_key_exists($routesGroupsKey, $this->routesGroups)) {
				$groupRoutes = $this->routesGroups[$routesGroupsKey];
			} else {
				$groupRoutes = [];
			}
			if ($prepend) {
				$newItem = [$routeName => $route];
				$groupRoutes = $newItem + $groupRoutes;
			} else {
				$groupRoutes[$routeName] = $route;
			}
			$this->routesGroups[$routesGroupsKey] = $groupRoutes;
		}
	}

	/**
	 * Clear all possible previously configured routes
	 * and set new given request routes again.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	], "eshop");`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		'products_list'	=> [
	 *			"pattern"			=>  [
	 *				"en"				=> "/products-list/<name>/<color>",
	 *				"de"				=> "/produkt-liste/<name>/<color>"
	 *			],
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		]
	 *	], ["en" => "eshop", "de" => "einkaufen"]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
	 * @param \MvcCore\Route[]|\MvcCore\Ext\Routers\Localizations\Route[]|array $routes 
	 *				Keyed array with routes, keys are route names or route
	 *				 `Controller::Action` definitions.
	 * @param string|array|NULL $groupNames 
	 *				Group name or names is first matched/parsed word(s) in 
	 *				requested path to group routes by to try to match only routes 
	 *				you really need, not all of them. If `NULL` by default, routes 
	 *				are inserted into default group. If argument is an array, it 
	 *				must contain localization keys and localized group names.
	 * @param bool $autoInitialize 
	 *				If `TRUE`, locale routes array is cleaned and then all 
	 *				routes (or configuration arrays) are sent into method 
	 *				`$router->AddRoutes();`, where are routes auto initialized 
	 *				for missing route names or route controller or route action
	 *				records, completed always from array keys. You can you 
	 *				`FALSE` to set routes without any change or auto-init, it 
	 *				could be useful to restore cached routes etc.
	 * @return \MvcCore\Ext\Routers\Localization
	 */
	public function SetRoutes ($routes = [], $groupNames = NULL, $autoInitialize = TRUE) {
		if ($autoInitialize) {
			$this->routes = [];
			$this->AddRoutes($routes, $groupNames);
		} else {
			$this->routes = $routes;
			$routesAreEmpty = count($routes) === 0;
			$noGroupNameDefined = $groupNames === NULL;
			if ($noGroupNameDefined) {
				if ($routesAreEmpty) {
					$this->routesGroups = [];
					$this->noUrlRoutes = [];
				}
				$this->routesGroups[''] = $routes;
			} else if (is_string($groupNames)) {
				$this->routesGroups[$groupNames] = $routes;
			} else if (is_array($groupNames)) {
				foreach ($groupNames as $routesLocalizationKey => $groupName)
					$this->routesGroups[$routesLocalizationKey . '/' . $groupName] = $routes;
			}
			$this->urlRoutes = [];
			foreach ($routes as $route) {
				$this->urlRoutes[$route->GetName()] = $route;
				$controllerAction = $route->GetControllerAction();
				if ($controllerAction !== ':') 
					$this->urlRoutes[$controllerAction] = $route;
				if ($noGroupNameDefined) {
					$routeGroupNames = $route->GetGroupName();
					$routesGroupsKeys = [];
					if ($routeGroupNames === NULL) {
						$routesGroupsKeys[] = '';
					} else if (is_string($routeGroupNames)) {
						$routesGroupsKeys[] = $routeGroupNames;
					} else if (is_array($routeGroupNames)) {
						foreach ($routeGroupNames as $routesLocalizationKey => $routeGroupName) 
							$routesGroupsKeys[] = $routesLocalizationKey . '/' . $routeGroupName;
					}
					foreach ($routesGroupsKeys as $routesGroupKey) {
						if (!array_key_exists($routesGroupKey, $this->routesGroups))
							$this->routesGroups[$routesGroupKey] = [];
						$this->routesGroups[$routesGroupKey][] = $route;
					}
				}
			}
			$this->anyRoutesConfigured = (!$routesAreEmpty) || $this->preRouteMatchingHandler !== NULL;
		}
		return $this;
	}

	/**
	 * Unset route from defined group. This method doesn't unset the route
	 * from router object to not be possible to create URL by given route anymore.
	 * This does route method: `\MvcCore\Route::RemoveRoute($routeName);`.
	 * @param \MvcCore\Route $route 
	 * @param string $routeName 
	 * @return void
	 */
	protected function removeRouteFromGroup (\MvcCore\IRoute $route, $routeName) {
		$routeGroups = $route->GetGroupName();
		$routesGroupsKeys = [];
		if ($routeGroups === NULL) {
			$routesGroupsKeys[] = '';
		} else if (is_string($routeGroups)) {
			$routesGroupsKeys[] = $routeGroups;
		} else if (is_array($routeGroups)) {
			foreach ($routeGroups as $routesLocalizationKey => $routeGroupName) 
				$routesGroupsKeys[] = $routesLocalizationKey . '/' . $routeGroupName;
		}
		foreach ($routesGroupsKeys as $routesGroupKey) {
			if (isset($this->routesGroups[$routesGroupKey])) 
			unset($this->routesGroups[$routesGroupKey][$routeName]);
		}
	}
	

	/***************************************************************************
	 *      Protected Methods For Parent Class Setters and Adding Methods      *
	 **************************************************************************/

	/**
	 * Detect and return `TRUE` if even one route configuration data are localized
	 * (return `TRUE` if one of `pattern`, `match` or `reverse` is an array).
	 * @param array $routeCfgData 
	 * @return boolean
	 */
	protected function isRouteCfgDataLocalized (array & $routeCfgData = []) {
		return (
			(isset($routeCfgData['pattern']) && is_array($routeCfgData['pattern'])) || 
			(isset($routeCfgData['match']) && is_array($routeCfgData['match'])) || 
			(isset($routeCfgData['reverse']) && is_array($routeCfgData['reverse']))
		);
	}

	/**
	 * Get always route instance from given route configuration data or instance 
	 * and return created instance from given configuration data or already given 
	 * instance.
	 * @param \MvcCore\Route|array $routeCfgOrRoute Route instance or
	 *																route config array.
	 * @return \MvcCore\Route
	 */
	protected function getRouteInstance (& $routeCfgOrRoute) {
		if ($routeCfgOrRoute instanceof \MvcCore\IRoute) 
			return $routeCfgOrRoute->SetRouter($this);
		$routeClass = $this->isRouteCfgDataLocalized($routeCfgOrRoute) 
			? self::$routeClassLocalized 
			: self::$routeClass;
		return $routeClass::CreateInstance($routeCfgOrRoute)->SetRouter($this);
	}

	/**
	 * Return localization string value for redirection URL but if localization 
	 * is defined by `GET` query string param, return `NULL` and set target 
	 * localization string into `GET` params to complete query string params 
	 * into redirect URL later. But if the target localization string is the same 
	 * as default localization, unset this param from `GET` params array return 
	 * `NULL` in query string localization definition case.
	 * @param \string[] $targetLocalization	Localization array, it could have one 
	 *										or two elements - lang and locale string.
	 * @return string|NULL
	 */
	protected function redirectLocalizationGetUrlValueAndUnsetGet ($targetLocalization) {
		$localizationUrlParam = static::URL_PARAM_LOCALIZATION;
		$targetLocalizationStr = implode(static::LANG_AND_LOCALE_SEPARATOR, $targetLocalization);
		if (isset($this->requestGlobalGet[$localizationUrlParam])) {
			if ($targetLocalizationStr === $this->defaultLocalizationStr) {
				unset($this->requestGlobalGet[$localizationUrlParam]);
			} else {
				$this->requestGlobalGet[$localizationUrlParam] = $targetLocalizationStr;
			}
			$targetLocalizationUrlValue = NULL;
		} else {
			$targetLocalizationUrlValue = $targetLocalizationStr;
		}

		return $targetLocalizationUrlValue;
	}
}
