<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends EntityRepository
{
	static protected function getOptions(array $options = array())
	{
		return array_merge(array(
/*  		'entity_type' => sfContext::getInstance()->getRequest()->getParameter('module'),  */
			'user_id'     => null,
/* 			'category'    => sfContext::getInstance()->getRequest()->getParameter('category', null),  */
			'category'    => null, 
/* 			'paginate'	  => true, */
			'locale'	  	=> 'en',
/* 			'limit'       => 25, */
		), $options);
	}
	
	public function getEvents(array $options = array())
	{
		$options = self::getOptions($options);
		
		$query = $this->createQueryBuilder('e')->select('e, t, d, i')
			->leftJoin('e.eventDates', 'd')
			->leftJoin('e.translations', 't')
			->leftJoin('e.images', 'i', 'WITH', 'i.main = '.true);
		
		if (isset($options['category'])) 
		{
			$query->andWhere('e.entityCategory = :category')->setParameter('category', $options['category']);	
		} 
		
		if (isset($options['archive'])) 
		{
			$query->andWhere('d.start <= :now')->orderBy('d.start', 'desc');	
		} 
		else 
		{
			$query->andWhere('d.start >= :now')->orderBy('d.start');	
		}			
			
		$query
			->setParameter('now', new \DateTime('now'))
			->andWhere('t.locale IN (:locale, \'en\')')->setParameter('locale', $options['locale'])
/* 			->setMaxResults($options['limit']) */;
			
		return $query;
		
/* 		return $options['paginate'] ? new Paginator($query , $fetchJoinCollection = true) : $query->getQuery()->getResult(); */
	}
	
	static public function getEventsPerMonth($year, $month, $options = array())
	{
		return $this->createQueryBuilder('e')->select('e, t, d')
			->leftJoin('e.eventDates', 'd')
			->leftJoin('e.translations', 't')
			->where('YEAR(d.start) = '.$year)
			->andWhere('MONTH(d.start) = '.$month)
			->orderBy('d.start')
			->getQuery()
			->getResult();
	}

	public function getEvent($id, $locale)
	{
		return $this->createQueryBuilder('e')->select('e, t, d, i')
			->leftJoin('e.eventDates', 'd')
			->leftJoin('e.translations', 't')
			->leftJoin('e.images', 'i')
			->andWhere('e.id = :id')->setParameter('id', $id)
			->andWhere('t.locale IN (:locale, \'en\')')->setParameter('locale', $locale)
			->getQuery()
			->getSingleResult();
	}
}
	