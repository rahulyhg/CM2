<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository;
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
			'paginate'	  => true,
			'locale'	  	=> 'en',
/* 			'limit'       => 25, */
		), $options);
	}
	
	public function getEvents(array $options = array())
	{
		$options = self::getOptions($options);

		$parameters = array(
			'now' => '2013-10-07',
			'locale' => $options['locale']
		);
		
		$eventDateRepository = $this->getEntityManager()->getRepository('CMBundle:EventDate');
		
		$query = $eventDateRepository->createQueryBuilder('d')->select('d, e, t, p, l, i')
			->join('d.event', 'e')
			->leftJoin('e.translations', 't')
			->leftJoin('e.posts', 'p', 'WITH', 'p.type = '.Post::TYPE_CREATION)
			->leftJoin('p.likes', 'l')
			->leftJoin('e.images', 'i', 'WITH', 'i.main = '.true);
		
		if (isset($options['category'])) {
			$query->andWhere('e.entityCategory = :category');
			$parameters['category'] = $options['category'];
		}
		
		if (isset($options['archive'])) {
			$query->andWhere('d.start <= :now')->orderBy('d.start', 'desc');	
		}
		else {
			$query->andWhere('d.start >= :now')->orderBy('d.start');	
		}
			
		$query
			->andWhere('t.locale in (:locale, \'en\')') // TODO: DA SISTEMARE CON UN MERGE
			->setParameters($parameters);
			
		
		return $options['paginate'] ? $query : $query->setMaxResults($options['limit'])->getQuery()->getResult();
	}
	
	public function getEventsPerMonth($year, $month, $options = array())
	{
		return $this->createQueryBuilder('e')->select('e, t, d, i')
			->leftJoin('e.eventDates', 'd')
			->leftJoin('e.translations', 't')
			->leftJoin('e.images', 'i', 'WITH', 'i.main = '.true)
			->where('SUBSTRING(d.start, 1, 4) = '.$year)
			->andWhere('SUBSTRING(d.start, 6, 2) = '.$month)
			->andWhere('t.locale IN (:locale, \'en\')')->setParameter('locale', $options['locale'])
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
	