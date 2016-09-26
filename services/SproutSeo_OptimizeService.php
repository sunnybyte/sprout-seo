<?php
namespace Craft;

class SproutSeo_OptimizeService extends BaseApplicationComponent
{
	/**
	 * @var
	 */
	public $context;

	/**
	 * @var
	 */
	public $divider;

	/**
	 * @var array
	 */
	public $codeMetadata = array();

	/**
	 * Add values to the master $this->codeMetadata array
	 *
	 * @param $meta
	 */
	public function updateMeta($meta)
	{
		if (count($meta))
		{
			foreach ($meta as $key => $value)
			{
				$this->codeMetadata[$key] = $value;
			}
		}
	}

	/**
	 * Get all metadata (Meta Tags and Structured Data) for the page
	 *
	 * @param $context
	 *
	 * @return \Twig_Markup
	 */
	public function getMetadata(&$context)
	{
		$optimizedMetadata = null;
		$this->context     = $context;

		$globals = sproutSeo()->globals->getGlobalMetadata();

		// get the sitemap info + urlFormat + $context->entry  $context->product ..
		$sitemapInfo = sproutSeo()->sitemap->getSitemapInfo($context);

		// Get our meta values
		$prioritizedMetadataModel = $this->getPrioritizedMetadataModel($sitemapInfo);

		$sitemapInfo['prioritizedMetadataModel'] = $prioritizedMetadataModel;
		$sitemapInfo['globals']                  = $globals;

		// Prepare our html for the template
		$optimizedMetadata .= $this->getMetaTagHtml($prioritizedMetadataModel);
		$optimizedMetadata .= $this->getStructuredDataHtml($sitemapInfo);
		$optimizedMetadata .= $this->getMainEntityStructuredDataHtml($sitemapInfo);

		return TemplateHelper::getRaw($optimizedMetadata);
	}

	/**
	 * Prioritize our meta data
	 * ------------------------------------------------------------
	 *
	 * Loop through and select the highest ranking value for each attribute in our SproutSeo_MetadataModel
	 *
	 * 1) Code Metadata
	 * 2) Element Metadata
	 * 3) Section Metadata
	 * 4) Global Metadata
	 * 5) Blank
	 *
	 * @param $sitemapInfo
	 *
	 * @return SproutSeo_MetadataModel
	 */
	public function getPrioritizedMetadataModel($sitemapInfo)
	{
		$metaLevels = SproutSeo_MetadataLevels::getConstants();

		$prioritizedMetadataLevels = array();

		foreach ($metaLevels as $key => $metaLevel)
		{
			$prioritizedMetadataLevels[$metaLevel] = null;
		}

		$prioritizedMetadataModel = new SproutSeo_MetadataModel();

		sproutSeo()->optimize->divider = craft()->plugins->getPlugin('sproutseo')->getSettings()->seoDivider;

		// Default to the Current URL
		$prioritizedMetadataModel->canonical  = SproutSeoOptimizeHelper::prepareCanonical($prioritizedMetadataModel);
		$prioritizedMetadataModel->ogUrl      = SproutSeoOptimizeHelper::prepareCanonical($prioritizedMetadataModel);
		$prioritizedMetadataModel->twitterUrl = SproutSeoOptimizeHelper::prepareCanonical($prioritizedMetadataModel);

		foreach ($prioritizedMetadataLevels as $level => $model)
		{
			$metadataModel = new SproutSeo_MetadataModel();

			$codeMetadata = sproutSeo()->metadata->getCodeMetadata($level, $sitemapInfo);

			$metadataModel = $metadataModel->setMeta($level, $codeMetadata);

			$prioritizedMetadataLevels[$level] = $metadataModel;

			foreach ($prioritizedMetadataModel->getAttributes() as $key => $value)
			{
				// Test for a value on each of our models in their order of priority
				if ($metadataModel->getAttribute($key))
				{
					$prioritizedMetadataModel[$key] = $metadataModel[$key];
				}

				// Make sure all our strings are trimmed
				if (is_string($prioritizedMetadataModel[$key]))
				{
					$prioritizedMetadataModel[$key] = trim($prioritizedMetadataModel[$key]);
				}
			}
		}

		$prioritizedMetadataModel->title = SproutSeoOptimizeHelper::prepareAppendedTitleValue(
			$prioritizedMetadataModel,
			$prioritizedMetadataLevels[SproutSeo_MetadataLevels::SectionMetadata],
			$prioritizedMetadataLevels[SproutSeo_MetadataLevels::GlobalMetadata]
		);

		$prioritizedMetadataModel->robots = SproutSeoOptimizeHelper::prepareRobotsMetadataValue($prioritizedMetadataModel->robots);

		return $prioritizedMetadataModel;
	}

	/**
	 * @param SproutSeo_MetadataModel $prioritizedMetadataModel
	 *
	 * @return string
	 */
	public function getMetaTagHtml(SproutSeo_MetadataModel $prioritizedMetadataModel)
	{
		$globals = sproutSeo()->globals->getGlobalMetadata();

		craft()->templates->setTemplatesPath(craft()->path->getPluginsPath());

		$output = craft()->templates->render('sproutseo/templates/_special/meta', array(
			'globals' => $globals,
			'meta'    => $prioritizedMetadataModel->getMetaTagData()
		));

		craft()->templates->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		return $output;
	}

	/**
	 * @param $sitemapInfo
	 *
	 * @return string
	 */
	public function getStructuredDataHtml($sitemapInfo)
	{
		craft()->templates->setTemplatesPath(craft()->path->getPluginsPath());

		$rawHtml = $this->getKnowledgeGraphLinkedData($sitemapInfo);

		$schemaHtml = craft()->templates->render('sproutseo/templates/_special/schema',
			array('jsonLd'=>$rawHtml)
		);

		craft()->templates->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		return $schemaHtml;
	}

	/**
	 * @param $sitemapInfo
	 *
	 * @return string
	 */
	public function getMainEntityStructuredDataHtml($sitemapInfo)
	{
		$schema = '';
		$prioritizedMetadataModel = $sitemapInfo['prioritizedMetadataModel'];

		if ($prioritizedMetadataModel)
		{
			$enabledMatchingSitemap = $prioritizedMetadataModel->schemaMap;

			if ($enabledMatchingSitemap)
			{
				$class = 'Craft\SproutSeo_'.$enabledMatchingSitemap.'SchemaMap';
				$schemaMap = new $class($prioritizedMetadataModel->getAttributes(), true, $sitemapInfo);

				$schema = $schemaMap->getSchema();
			}
		}

		return $schema;
	}

	public function getKnowledgeGraphLinkedData($sitemapInfo)
	{
		$output = null;

		$globals = $sitemapInfo['globals'];

		if ($identityType = $globals->identity['@type'])
		{
			// Determine if we have an Organization or Person Schema Type
			$schemaModel = 'Craft\SproutSeo_' . $identityType . 'SchemaMap';

			$identitySchema = new $schemaModel(array(
				'globals' => $globals
			), true, $sitemapInfo);

			$output = $identitySchema->getSchema();
		}

		return TemplateHelper::getRaw($output);
	}
}
