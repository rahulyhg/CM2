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
		
		$postRepository = $this->getEntityManager()->getRepository('CMBundle:Post');
		
		$query = $postRepository->createQueryBuilder('p')->select('p, et, e, t')
			->leftJoin('p.entity', 'et')
			->from('CM\CMBundle\Entity\Event', 'e')
			// ->andWhere('et.id = e.id')
			// ->leftJoin('e.eventDates', 'd')
			->leftJoin('e.translations', 't');
			// ->leftJoin('e.images', 'i', 'WITH', 'i.main = '.true);
		
		// if (isset($options['category'])) 
		// {
		// 	$query->andWhere('e.entityCategory = :category')->setParameter('category', $options['category']);	
		// } 
		
		// if (isset($options['archive'])) 
		// {
		// 	$query->andWhere('d.start <= :now')->orderBy('d.start', 'desc');	
		// }
		// else
		// {
		// 	$query->andWhere('d.start >= :now')->orderBy('d.start');	
		// }			
			
		$query
			// ->setParameter('now', new \DateTime('now'))
			->andWhere('t.locale IN (:locale, \'en\')')->setParameter('locale', $options['locale']);

		return $query->setMaxResults(2)->getQuery()->getResult();
		
		$query->setMaxResults(1);
		$query = $query->getQuery();
		echo 'sql:<br/><pre>'.$query->getSQL().'</pre>';
		echo 'parameters:<br/><pre>'.var_dump($query->getParameters()).'</pre>';
		echo '<br/><br/>';
		$results = $query->getResult();
		echo 'Number of results: '.count($results).'<br/><br/>';
		foreach ($results as $post) {
			echo $post->getId().'<pre>'.var_dump(get_class_methods($post)).'</pre><br/>';
		}
		die;
		
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
	