<?php

namespace CM\CMBundle\DataFixtures\ORM\Entities;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CM\CMBundle\Entity\Article;
use CM\CMBundle\Entity\Image;
use CM\CMBundle\Entity\Post;
use CM\CMBundle\Entity\Like;
use CM\CMBundle\Entity\User;
use CM\CMBundle\Entity\EntityUser;
use CM\CMBundle\DataFixtures\ORM;

class ArticleFixtures
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private $articles = array(
        array('title' => 'Anche i delfini si sballano. La loro droga è l\'aria di un pesce palla',
            'text' => 'Non è una novità che i delfini siano animali caratterialmente molto simili agli uomini. Noto il loro coraggio, l\'intelligenza, la loro gelosia e la naturale inclinazione a fare scherzi. Ma anche una spiccata sensibilità che li rende particolarmente amati da grandi e piccoli. Le somiglianze tra noi e questi simpatici mammiferi, però, non fiscono qui. Perché, a quanto pare, possiedono anche alcuni dei nostri vizi. Come quello di sballarsi.
            A fare la straordinaria scoperta un gruppo di scienziati durante la lavorazione della serie Tv \'Dolphins: Spy in the Pod\' , trasmessa dall\'emittente britannica Bbc. In una delle scene si vedono, infatti, alcuni esemplari che sembrano ottenere effetti \'stupefacenti\' aspirando l\'aria di una particolare razza di pesce palla. I delfini vengono ripresi dalle telecamere mentre si passano delicatamente il pesce tra di loro. L\'aria rilasciata dal pesce palla, in realtà contiene delle sostanze tossiche usate come meccanismo di  difesa e deterrente per gli altri pesci predatori.',
            'source' => 'http://www.repubblica.it/scienze/2014/01/02/news/delfini_si_drogano_aria_pesce_palla_tossico-74999432/',
            'date' => '2014-1-2',
            'img' => 'bb01acb97854b24ed23598bd4f055eba.jpeg'
        ),
        array('title' => 'Il tatto è più sensibile che mai: con le dita "sentiamo" molecole grandi',
            'text' => 'Percepire, solo toccandole, molecole delle dimensioni di pochi nanometri (milionesimi di millimetro). Non servono strumenti di misura, ma soltanto il nostro dito indice. Lo ha provato, oggi, un gruppo di ricerca dell\'Istituto Reale di Tecnologia di Stoccolma, insieme ad altri istituti: il gruppo è arrivato al limite massimo del tatto umano, dimostrando come questo senso sia molto più fino di quello che si pensava. Fino ad ora, infatti, la sensibilità del dito era stata stimata intorno al micrometro (millesimo di millimetro), cioè circa 100 volte meno acuta rispetto a quella misurata oggi. Lo studio,  recentemente pubblicato sulla rivista di Nature, Scientific Reports, fa intravedere suggestive applicazioni per touch screen che daranno la sensazione di una superficie ruvida o di un bosco.',
            'source' => 'http://www.repubblica.it/scienze/2013/11/07/news/tatto_dita_molecole-70426606/#gallery-slider=70442015',
            'date' => '2013-11-7',
            'img' => 'ff9398d3d47436e2b4f72874a2c766fd.jpeg'
        ),
        array('title' => 'Elisabeth Jacquet de la Guerre - Pieces de clavecin',
            'text' => '',
            'source' => 'http://javanese.imslp.info/files/imglnks/usimg/3/31/IMSLP18522-LaGuerre_PiecesDeClavecin_Complete.pdf',
            'date' => '2014-1-1',
            'img' => 'bb01acb97854b24ed23598bd4f055eba.jpeg'
        ),
    );

    /**
     * {@inheritDoc}
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(AbstractFixture $fixture, ObjectManager $manager, $i)
    {
        $articleNum = rand(0, count($this->articles) - 1);
        $article = new Article;
        $article->setSource($this->articles[$articleNum]['source'])
            ->setDate(new \DateTime($this->articles[$articleNum]['date']));

        $article->setTitle($this->articles[$articleNum]['title'].' (en)')
            ->setText($this->articles[$articleNum]['text']);

        if (0 == rand(0, 2)) {
            $article->translate('it')
                ->setTitle($this->articles[$articleNum]['title'].' (it)')
                ->setText($this->articles[$articleNum]['text']);
        }

        if (0 == rand(0, 4)) {
            $article->translate('fr')
                ->setTitle($this->articles[$articleNum]['title'].' (fr)')
                ->setText($this->articles[$articleNum]['text']);
        }

        $manager->persist($article);

        $article->mergeNewTranslations();
        
        $userNum = rand(1, ORM\UserFixtures::countPeople());
        $user = $manager->merge($fixture->getReference('user-'.$userNum));

        if (rand(0, 4) > 0) {
            $image = new Image;
            $image->setImg($this->articles[$articleNum]['img'])
                ->setText('main image for article "'.$article->getTitle().'"')
                ->setMain(true)
                ->setUser($user);
            $article->addImage($image);

            $manager->persist($article);
            $manager->flush();   
                
            for ($j = rand(1, 4); $j > 0; $j--) {
                $image = new Image;
                $image->setImg($this->articles[$articleNum]['img'])
                    ->setText('image number '.$j.' for article "'.$article->getTitle().'"')
                    ->setMain(false)
                    ->setUser($user);
                
                $article->addImage($image);
            }

        }

        $category = $manager->merge($fixture->getReference('article_category-'.rand(1, 6)));
        $category->addEntity($article);

        $page = null;
        $group = null;
        $pageOrGroup = rand(0, 100);
        if ($pageOrGroup < 20) {
            $page = $manager->merge($fixture->getReference('page-'.rand(1, ORM\PageFixtures::countPages())));
        } elseif ($pageOrGroup < 40) {
            $group = $manager->merge($fixture->getReference('group-'.rand(1, ORM\GroupFixtures::countGroups())));
        }

        $post = $this->container->get('cm.post_center')->getNewPost($user, $user);
        $post->setPage($page);
        $post->setGroup($group);

        $article->addPost($post);
        
        $userTags = array();
        for ($j = 1; $j < rand(1, 3); $j++) {
            $userTags[] = $manager->merge($fixture->getReference('user_tag-'.rand(1, 10)));
        }
        
        $article->addUser(
            $user,
            true, // admin
            EntityUser::STATUS_ACTIVE,
            true, // notification
            $userTags
        );

        $numbers = range(1, ORM\UserFixtures::countPeople());
        unset($numbers[$userNum - 1]);
        shuffle($numbers);
        for ($j = 0; $j < rand(0, 6); $j++) {
            $otherUser = $manager->merge($fixture->getReference('user-'.$numbers[$j]));
            
            $userTags = array();
            for ($k = 1; $k < rand(1, 3); $k++) {
                $userTags[] = $manager->merge($fixture->getReference('user_tag-'.rand(1, 10)));
            }

            $article->addUser(
                $otherUser,
                !rand(0, 3), // admin
                EntityUser::STATUS_PENDING,
                true, // notification
                $userTags
            );
        }

        $manager->persist($article);
        
        if ($i % 10 == 9) {
            $manager->flush();
        }

        $fixture->addReference('article-'.$i, $article);
    }
}