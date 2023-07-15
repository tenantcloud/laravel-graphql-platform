<?php

namespace TenantCloud\GraphQLPlatform\Validation;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

class LaravelCompositeTranslatorAdapter implements TranslatorInterface
{
	use TranslatorTrait {
		trans as symfonyTrans;
	}

	public function __construct(
		private readonly Translator $translator,
	) {
	}

	public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
	{
		$translated = $this->symfonyTrans($id, $parameters, $domain, $locale);

		if ($id !== $translated) {
			return $translated;
		}

		if (Arr::has($parameters, '%count%')) {
			return $this->translator->choice($id, $parameters, $parameters['%count%'], $locale);
		}

		return $this->translator->get($id, $parameters, $locale);
	}

	public function getLocale(): string
	{
		return $this->translator->getLocale();
	}
}
