<?php
namespace Craft;

class SproutSeo_MetaTagsModel extends BaseModel
{
	protected $basicMeta = array();
	protected $robotsMeta = array();
	protected $geographicMeta = array();
	protected $openGraphMeta = array();
	protected $twitterCardsMeta = array();

	protected function defineAttributes()
	{
		$sitemap = array (
			'elementGroupId'  => array(AttributeType::Number),
			'type'            => array(AttributeType::String),
			'priority'        => array(
				AttributeType::Number,
				'maxLength' => 2,
				'decimals'  => 1,
				'default'   => '0.5',
				'required'  => true
			),
			'changeFrequency' => array(
				AttributeType::String,
				'maxLength' => 7,
				'default'   => 'weekly',
				'required'  => true
			),
			'enabled'         => array(
				AttributeType::Bool,
				'default'  => false,
				'required' => true
			),
			'isCustom'         => array(
				AttributeType::Bool,
				'default'  => false,
				'required' => true
			),
			'schemaMap'       => array(AttributeType::String),
			'dateUpdated'     => array(AttributeType::DateTime),
			'dateCreated'     => array(AttributeType::DateTime),
			'uid'             => array(AttributeType::String),
		);

		$metaTags = array(
			'id'             => array(AttributeType::Number),
			'entryId'        => array(AttributeType::Number),
			'default'        => array(AttributeType::String),
			'name'           => array(AttributeType::String),
			'handle'         => array(AttributeType::String),
			'appendSiteName' => array(AttributeType::String, 'default' => null),
			'url'            => array(AttributeType::String),
		);

		$this->basicMeta = array(
			'title'        => array(AttributeType::String),
			'elementTitle' => array(AttributeType::String),
			'description'  => array(AttributeType::String),
			'metaImage'    => array(AttributeType::String),
			'keywords'     => array(AttributeType::String),
			'author'       => array(AttributeType::String),
			'publisher'    => array(AttributeType::String),
			'locale'       => array(AttributeType::String),
		);

		$this->robotsMeta = array(
			'robots'    => array(AttributeType::String),
			'canonical' => array(AttributeType::String),
		);

		$this->geographicMeta = array(
			'region'    => array(AttributeType::String),
			'placename' => array(AttributeType::String),
			'position'  => array(AttributeType::String),
			'latitude'  => array(AttributeType::String),
			'longitude' => array(AttributeType::String),
		);

		$this->openGraphMeta = array(
			'ogTitle'       => array(AttributeType::String),
			'ogType'        => array(AttributeType::String),
			'ogUrl'         => array(AttributeType::String),
			'ogImage'       => array(AttributeType::String),
			'ogImageSecure' => array(AttributeType::String),
			'ogImageWidth'  => array(AttributeType::String),
			'ogImageHeight' => array(AttributeType::String),
			'ogImageType'   => array(AttributeType::String),
			'ogAuthor'      => array(AttributeType::String),
			'ogPublisher'   => array(AttributeType::String),
			'ogSiteName'    => array(AttributeType::String),
			'ogDescription' => array(AttributeType::String),
			'ogAudio'       => array(AttributeType::String),
			'ogVideo'       => array(AttributeType::String),
			'ogLocale'      => array(AttributeType::String),
		);

		$this->twitterCardsMeta = array(
			'twitterCard'                    => array(AttributeType::String),
			'twitterSite'                    => array(AttributeType::String),
			'twitterTitle'                   => array(AttributeType::String),
			'twitterCreator'                 => array(AttributeType::String),
			'twitterDescription'             => array(AttributeType::String),
			'twitterUrl'                     => array(AttributeType::String),
			'twitterImage'                   => array(AttributeType::String),
			'twitterPlayer'                  => array(AttributeType::String),
			'twitterPlayerStream'            => array(AttributeType::String),
			'twitterPlayerStreamContentType' => array(AttributeType::String),
			'twitterPlayerWidth'             => array(AttributeType::String),
			'twitterPlayerHeight'            => array(AttributeType::String),
		);

		$attributes = array_merge(
			$sitemap,
			$metaTags,
			$this->basicMeta,
			$this->robotsMeta,
			$this->geographicMeta,
			$this->openGraphMeta,
			$this->twitterCardsMeta
		);

		return $attributes;
	}

	public function getMetaTagData()
	{
		$metaTagData = array();

		$metaTagData['basic']     = $this->getBasicMetaTagData();
		$metaTagData['robots']    = $this->getRobotsMetaTagData();
		$metaTagData['geo']       = $this->getGeographicMetaTagData();
		$metaTagData['openGraph'] = $this->getOpenGraphMetaTagData();
		$metaTagData['twitter']   = $this->getTwitterCardMetaTagData();

		return $metaTagData;
	}

	/**
	 * @param string $type
	 * @param array  $overrideInfo
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function setMeta($type = 'global', $overrideInfo = array())
	{
		switch ($type)
		{
			case SproutSeo_MetaLevels::Entry:
				$this->setAttributes($this->getEntryOverride($overrideInfo));
				break;

			case SproutSeo_MetaLevels::Code:
				$this->setAttributes($this->getCodeOverride($overrideInfo));
				break;

			case SproutSeo_MetaLevels::MetaTagsGroup:
				$this->setAttributes($this->getMetaTagsGroup($overrideInfo));
				break;

			case SproutSeo_MetaLevels::Global:
				$globals                = sproutSeo()->schema->getGlobals();
				$globalFallbackMetaTags = $globals->meta;

				$this->setAttributes($globalFallbackMetaTags);
				break;
		}

		SproutSeoOptimizeHelper::prepareAssetUrls($this);

		return $this;
	}

	/**
	 * Get Meta Tag Content based on an Element ID
	 *
	 * @param $overrideInfo
	 *
	 * @return array
	 */
	protected function getEntryOverride($overrideInfo)
	{
		if (isset($overrideInfo['entryId']))
		{
			// @todo - revisit when adding internationalization
			$locale        = (defined('CRAFT_LOCALE') ? CRAFT_LOCALE : craft()->locale->getId());
			$entryOverride = sproutSeo()->metaTags->getMetaTagContentByEntryId($overrideInfo['entryId'], $locale);

			return $entryOverride->getAttributes();
		}

		return array();
	}

	/**
	 * Process any Meta Tags provided in via the templates and create a SproutSeo_MetaTagsModel
	 *
	 * @param $overrideInfo
	 *
	 * @return SproutSeo_MetaTagsModel
	 */
	protected function getCodeOverride($overrideInfo)
	{
		if (!empty($overrideInfo))
		{
			return $overrideInfo;
		}

		return array();
	}

	/**
	 * Create our default Meta Tag Group SproutSeo_MetaTagsModel
	 *
	 * @param $overrideInfo
	 *
	 * @return SproutSeo_MetaTagsModel
	 */
	protected function getMetaTagsGroup($overrideInfo)
	{
		if (isset($overrideInfo['slug']))
		{
			$metaTagsModel = sproutSeo()->metaTags->getMetaTagGroupByUrl($overrideInfo['slug']);

			return $metaTagsModel->getAttributes();
		}

		return array();
	}

	protected function getBasicMetaTagData()
	{
		$tagData = array();

		foreach ($this->basicMeta as $key => $value)
		{
			if ($this->{$key})
			{
				$value         = craft()->config->parseEnvironmentString($this->{$key});
				$tagData[$key] = $value;
			}
		}

		return $tagData;
	}

	protected function getRobotsMetaTagData()
	{
		$tagData = array();

		foreach ($this->robotsMeta as $key => $value)
		{
			if ($this->{$key})
			{
				$value = $this->{$key};

				if ($key == 'robots')
				{
					$value = $this->robots;
				}

				$tagData[$key] = $value;
			}
		}

		return $tagData;
	}

	protected function getGeographicMetaTagData()
	{
		$tagData = array();

		foreach ($this->geographicMeta as $key => $value)
		{
			if ($key == 'latitude' or $key == 'longitude')
			{
				break;
			}

			if ($this->{$key})
			{
				$value = $this[$key];

				if ($key == 'position')
				{
					$value = SproutSeoOptimizeHelper::prepareGeoPosition($this);
				}

				$tagData[$this->getMetaTagName($key)] = $value;
			}
		}

		return $tagData;
	}

	protected function getOpenGraphMetaTagData()
	{
		$tagData = array();

		foreach ($this->openGraphMeta as $key => $value)
		{
			if ($this->{$key})
			{
				$value                                = craft()->config->parseEnvironmentString($this->{$key});
				$tagData[$this->getMetaTagName($key)] = $value;
			}
		}

		return $tagData;
	}

	protected function getTwitterCardMetaTagData()
	{
		$tagData = array();

		foreach ($this->twitterCardsMeta as $key => $value)
		{
			if ($this->{$key})
			{
				$value                                = craft()->config->parseEnvironmentString($this->{$key});
				$tagData[$this->getMetaTagName($key)] = $value;
			}
		}

		return $tagData;
	}

	protected function getMetaTagName($handle)
	{
		// Map tag names to their handles
		$tagNames = array(

			// Geographic
			'region'                         => 'geo.region',
			'placename'                      => 'geo.placename',
			'position'                       => 'geo.position',

			// Open Graph
			'ogTitle'                        => 'og:title',
			'ogType'                         => 'og:type',
			'ogUrl'                          => 'og:url',
			'ogImage'                        => 'og:image',
			'ogImageSecure'                  => 'og:image:secure_url',
			'ogImageWidth'                   => 'og:image:width',
			'ogImageHeight'                  => 'og:image:height',
			'ogImageType'                    => 'og:image:type',
			'ogAuthor'                       => 'og:author',
			'ogPublisher'                    => 'og:publisher',
			'ogSiteName'                     => 'og:site_name',
			'ogDescription'                  => 'og:description',
			'ogAudio'                        => 'og:audio',
			'ogVideo'                        => 'og:video',
			'ogLocale'                       => 'og:locale',

			// Twitter Cards
			'twitterCard'                    => 'twitter:card',
			'twitterSite'                    => 'twitter:site',
			'twitterCreator'                 => 'twitter:creator',
			'twitterTitle'                   => 'twitter:title',
			'twitterDescription'             => 'twitter:description',
			'twitterUrl'                     => 'twitter:url',
			'twitterImage'                   => 'twitter:image',
			'twitterPlayer'                  => 'twitter:player',
			'twitterPlayerStream'            => 'twitter:player:stream',
			'twitterPlayerStreamContentType' => 'twitter:player:stream:content_type',
			'twitterPlayerWidth'             => 'twitter:player:width',
			'twitterPlayerHeight'            => 'twitter:player:height',
		);

		return $tagNames[$handle];
	}
}