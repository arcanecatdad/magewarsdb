<?php 

namespace AppBundle\Repository;

class CycleRepository extends TranslatableRepository
{
	function __construct($entityManager)
	{
		parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Cycle'));
	}
}
