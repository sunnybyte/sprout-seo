<?php
namespace Craft;

class SproutSeo_PersonSchemaMap extends BaseSproutSeoSchemaMap
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Person';
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'Person';
	}

	/**
	 * @return array|null
	 */
	public function getAttributes()
	{
		$person = $this->sitemapInfo['globals']['identity'];
		$socialProfiles = $this->sitemapInfo['globals']['social'];

		if (!$person)
		{
			return null;
		}

		$schema['name']                = isset($person['name']) ? $person['name'] : null;
		$schema['alternateEntityName'] = isset($person['alternateEntityName']) ? $person['alternateEntityName'] : null;
		$schema['description']         = isset($person['description']) ? $person['description'] : null;
		$schema['url']                 = isset($person['url']) ? $person['url'] : null;
		$schema['telephone']           = isset($person['telephone']) ? $person['telephone'] : null;
		$schema['email']               = isset($person['email']) ? $person['email'] : null;

		// @todo - consider renaming identity logo to the more generic "image" label
		if (isset($schema['logo'][0]))
		{
			$schema['image'] = $this->getSchemaImageById($schema['logo'][0]);
		}

		$schema['gender']              = isset($person['gender']) ? $person['gender'] : null;
		$schema['birthplace']          = isset($person['birthplace']) ? $person['birthplace'] : null;

		if (count($socialProfiles))
		{
			$profileUrls = array();

			foreach ($socialProfiles as $socialProfile)
			{
				$profileUrls[] = $socialProfile['url'];
			}

			$schema['sameAs'] = array_values($profileUrls);
		}


		return array_filter($schema);
	}
}