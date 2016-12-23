<?php
use Exception as E;
use Magento\Framework\DataObject;
use Raven_Client as C;

/**
 * 2016-12-22
 * @param DataObject|mixed[]|mixed|E $v
 * @param array(string => mixed) $context [optional]
 */
function df_sentry($v, array $context = []) {
	if (true || !df_my_local()) {
		$context = df_extend([
			// 2016-22-22
			// https://docs.sentry.io/clients/php/usage/#optional-attributes
			'extra' => []
			,'tags' => [
				'PHP' => phpversion()
				,'Magento' => df_magento_version()
				// 2016-12-22
				// К сожалению, использовать «/» в имени тега нельзя.
				,'mage2pro_core' => df_package_version('mage2pro/core')
			]
		], $context);
		if ($v instanceof E) {
			// 2016-12-22
			// https://docs.sentry.io/clients/php/usage/#reporting-exceptions
			df_sentry_m()->captureException($v, $context);
		}
		else {
			$v = df_dump($v);
			// 2016-12-22
			// https://docs.sentry.io/clients/php/usage/#reporting-other-errors
			df_sentry_m()->captureMessage($v, [], [
				/**
				 * 2016-12-23
				 * @used-by \Raven_Client::capture():
					if (!isset($data['level'])) {
						$data['level'] = self::ERROR;
					}
				 * https://github.com/mage2pro/sentry/blob/1.6.4/lib/Raven/Client.php#L640-L642
				 * При использовании @see \Raven_Client::DEBUG у сообщения в списке сообщений
				 * в интерфейсе Sentry не будет никакой метки.
				 * При использовании @see \Raven_Client::INFO у сообщения в списке сообщений
				 * в интерфейсе Sentry будет синяя метка «Info».
				 */
				'level' => C::DEBUG
			] + $context);
		}
	}
}

/**
 * 2016-12-22
 * @return C
 */
function df_sentry_m() {return dfcf(function() {
	/** @var C $result */
	$result = new C(
		'https://0574710717d5422abd1c5609012698cd:32ddadc0944c4c1692adbe812776035f@sentry.io/124181'
		,[
			/**
			 * 2016-12-22
			 * Не используем стандартные префиксы: @see \Raven_Client::getDefaultPrefixes()
			 * потому что они включают себя весь @see get_include_path()
			 * в том числе и папки внутри Magento (например: lib\internal),
			 * и тогда, например, файл типа
			 * C:\work\mage2.pro\store\lib\internal\Magento\Framework\App\ErrorHandler.php
			 * будет обрезан как Magento\Framework\App\ErrorHandler.php
			 */
			'prefixes' => [BP . DIRECTORY_SEPARATOR]
		]
	);
	/**
	 * 2016-12-22
	 * «The root path to your application code.»
	 * https://docs.sentry.io/clients/php/config/#available-settings
	 * У Airbrake для Ruby есть аналогичный параметр — «root_directory»:
	 * https://github.com/airbrake/airbrake-ruby/blob/v1.6.0/README.md#root_directory
	 */
	$result->setAppPath(BP);
	return $result;
});}