<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\Decklist;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Service\CardsData;

class DecklistsController extends Controller
{

    /**
     * displays the lists of decklists
     */
    public function listAction ($type, $code = null, $page = 1, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));

        $limit = 30;
        if($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;

        $pagetitle = "Decklists";
        $header = '';
                  
        $securityContext = $this->get('security.authorization_checker');

        switch($type) {
            case 'find':
                $result = $this->get('decklists')->find($start, $limit, $request);
                $pagetitle = "Decklist search results";
                $header = $this->searchForm($request);
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if(!$user) {
                    $result = array('decklists' => array(), 'count' => 0);
                } else {
                    $result = $this->get('decklists')->favorites($user->getId(), $start, $limit);
                }
                $pagetitle = "Favorite Decklists";
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if(!$user) {
                    $result = array('decklists' => array(), 'count' => 0);
                } else {
                    $result = $this->get('decklists')->by_author($user->getId(), $start, $limit);
                }
                $pagetitle = "My Decklists";
                break;
            case 'recent':
                $result = $this->get('decklists')->recent($start, $limit);
                $pagetitle = "Recent Decklists";
                break;
            case 'dotw':
                $result = $this->get('decklists')->dotw($start, $limit);
                $pagetitle = "Decklist of the week";
                break;
            case 'halloffame':
                $result = $this->get('decklists')->halloffame($start, $limit);
                $pagetitle = "Hall of Fame";
                break;
            case 'hottopics':
                $result = $this->get('decklists')->hottopics($start, $limit);
                $pagetitle = "Hot Topics";
                break;
            case 'tournament':
                $result = $this->get('decklists')->tournaments($start, $limit);
                $pagetitle = "Tournaments";
                break;
            case 'trashed':
                if(!$securityContext->isGranted('ROLE_MODERATOR')) {
                    throw $this->createAccessDeniedException('Access denied');
                }
                $result = $this->get('decklists')->trashed($start, $limit);
                $pagetitle = "Trashed decklists";
                break;
            case 'restored':
                if(!$securityContext->isGranted('ROLE_MODERATOR')) {
                    throw $this->createAccessDeniedException('Access denied');
                }
                $result = $this->get('decklists')->restored($start, $limit);
                $pagetitle = "Restored decklists";
                break;
            case 'popular':
            default:
                $result = $this->get('decklists')->popular($start, $limit);
                $pagetitle = "Popular Decklists";
                break;
        }

        $decklists = $result['decklists'];
        $maxcount = $result['count'];

        $dbh = $this->get('doctrine')->getConnection();
        $factions = $dbh->executeQuery(
                        "SELECT
				f.name,
				f.code
				from faction f
				order by f.side_id asc, f.name asc")
                ->fetchAll();

        $packs = $dbh->executeQuery(
                        "SELECT
				p.name,
				p.code
				from pack p
				where p.date_release is not null
				order by p.date_release desc
				limit 0,5")
                ->fetchAll();

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $params = $request->query->all();
        $params['type'] = $type;

        $pages = array();
        for($page = 1; $page <= $nbpages; $page ++) {
            $pages[] = array(
                "numero" => $page,
                "url" => $this->generateUrl($route, $params + array(
                    "page" => $page
                )),
                "current" => $page == $currpage
            );
        }

        return $this->render('AppBundle:Decklist:decklists.html.twig', array(
                    'pagetitle' => $pagetitle,
                    'pagedescription' => "Browse the collection of thousands of premade decks.",
                    'decklists' => $decklists,
                    'packs' => $packs,
                    'factions' => $factions,
                    'url' => $request
                            ->getRequestUri(),
                    'header' => $header,
                    'route' => $route,
                    'pages' => $pages,
                    'type' => $type,
                    'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, $params + array(
                        "page" => $prevpage
                    )),
                    'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + array(
                        "page" => $nextpage
                    ))
                        ), $response);
    }

    public function searchAction (Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));

        $dbh = $this->get('doctrine')->getConnection();
        $factions = $dbh->executeQuery(
                        "SELECT
				f.name,
				f.code
				from faction f
				order by f.side_id asc, f.name asc")
                ->fetchAll();

        $categories = array();
        $on = 0;
        $off = 0;
        $categories[] = array("label" => "Core / Deluxe", "packs" => array());
        $list_cycles = $this->get('doctrine')->getRepository('AppBundle:Cycle')->findBy(array(), array("position" => "ASC"));
        foreach($list_cycles as $cycle) {
            $size = count($cycle->getPacks());
            if($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }
            $first_pack = $cycle->getPacks()[0];
            if($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = $first_pack->getDateRelease() !== NULL;
                if($checked) {
                    $on++;
                } else {
                    $off++;
                }
                $categories[0]["packs"][] = array("id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === NULL);
            } else {
                $category = array("label" => $cycle->getName(), "packs" => array());
                foreach($cycle->getPacks() as $pack) {
                    $checked = $pack->getDateRelease() !== NULL;
                    if($checked) {
                        $on++;
                    } else {
                        $off++;
                    }
                    $category['packs'][] = array("id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
                }
                $categories[] = $category;
            }
        }

        $em = $this->getDoctrine()->getManager();
        $list_mwl = $em->getRepository('AppBundle:Mwl')->findBy(array(), array('dateStart' => 'DESC'));

        return $this->render('AppBundle:Search:search.html.twig', array(
                    'pagetitle' => 'Decklist Search',
                    'url' => $request
                            ->getRequestUri(),
                    'factions' => $factions,
                    'form' => $this->renderView('AppBundle:Search:form.html.twig', array(
                        'allowed' => $categories,
                        'on' => $on,
                        'off' => $off,
                        'author' => '',
                        'title' => '',
                        'list_mwl' => $list_mwl,
                        'mwl_code' => '',
                            )
                    ),
                        ), $response);
    }

    private function searchForm (Request $request)
    {
        $dbh = $this->get('doctrine')->getConnection();

        $cards_code = $request->query->get('cards');
        $faction_code = filter_var($request->query->get('faction'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_title = filter_var($request->query->get('title'), FILTER_SANITIZE_STRING);
        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');
        $mwl_code = $request->query->get('mwl_code');

        if(!is_array($packs)) {
            $packs = $dbh->executeQuery("select id from pack")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $locale = $request->query->get('_locale') ?: $request->getLocale();

        $categories = array();
        $on = 0;
        $off = 0;
        $categories[] = array("label" => "Core / Deluxe", "packs" => array());
        $list_cycles = $this->get('doctrine')->getRepository('AppBundle:Cycle')->findBy(array(), array("position" => "ASC"));
        foreach($list_cycles as $cycle) {
            $size = count($cycle->getPacks());
            if($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }
            $first_pack = $cycle->getPacks()[0];
            if($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if($checked) {
                    $on++;
                } else {
                    $off++;
                }
                $categories[0]["packs"][] = array("id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === NULL);
            } else {
                $category = array("label" => $cycle->getName(), "packs" => array());
                foreach($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if($checked) {
                        $on++;
                    } else {
                        $off++;
                    }
                    $category['packs'][] = array("id" => $pack->getId(), "label" => $pack->getName($locale), "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
                }
                $categories[] = $category;
            }
        }

        $em = $this->getDoctrine()->getManager();
        $list_mwl = $em->getRepository('AppBundle:Mwl')->findBy(array(), array('dateStart' => 'DESC'));


        $params = array(
            'allowed' => $categories,
            'on' => $on,
            'off' => $off,
            'author' => $author_name,
            'title' => $decklist_title,
            'list_mwl' => $list_mwl,
            'mwl_code' => $mwl_code,
        );
        $params['sort_' . $sort] = ' selected="selected"';
        $params['faction_' . CardsData::$faction_letters[$faction_code]] = ' selected="selected"';

        if(!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery(
                            "SELECT
    				c.title,
    				c.code,
                    f.code faction_code
    				from card c
                    join faction f on f.id=c.faction_id
                    where c.code in (?)
    				order by c.code desc", array($cards_code), array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY))
                    ->fetchAll();

            $params['cards'] = '';
            foreach($cards as $card) {
                $params['cards'] .= $this->renderView('AppBundle:Search:card.html.twig', $card);
            }
        }

        return $this->renderView('AppBundle:Search:form.html.twig', $params);
    }

    public function diffAction ($decklist1_id, $decklist2_id)
    {
        if($decklist1_id > $decklist2_id) {
            return $this->redirect($this->generateUrl('decklists_diff', ['decklist1_id' => $decklist2_id, 'decklist2_id' => $decklist1_id]));
        }
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        /* @var $d1 Decklist */
        $d1 = $em->getRepository('AppBundle:Decklist')->find($decklist1_id);
        /* @var $d2 Decklist */
        $d2 = $em->getRepository('AppBundle:Decklist')->find($decklist2_id);

        if(!$d1 || !$d2) {
            throw new NotFoundHttpException("Unable to find decklists.");
        }

        $decks = [$d1->getContent(), $d2->getContent()];

        list($listings, $intersect) = $this->get('diff')->diffContents($decks);

        $content1 = [];
        foreach($listings[0] as $code => $qty) {
            $card = $em->getRepository('AppBundle:Card')->findOneBy(['code' => $code]);
            if($card) {
                $content1[] = [
                    'title' => $card->getTitle(),
                    'code' => $code,
                    'qty' => $qty
                ];
            }
        }

        $content2 = [];
        foreach($listings[1] as $code => $qty) {
            $card = $em->getRepository('AppBundle:Card')->findOneBy(['code' => $code]);
            if($card) {
                $content2[] = [
                    'title' => $card->getTitle(),
                    'code' => $code,
                    'qty' => $qty
                ];
            }
        }

        $shared = [];
        foreach($intersect as $code => $qty) {
            $card = $em->getRepository('AppBundle:Card')->findOneBy(['code' => $code]);
            if($card) {
                $shared[] = [
                    'title' => $card->getTitle(),
                    'code' => $code,
                    'qty' => $qty
                ];
            }
        }


        return $this->render('AppBundle:Diff:decklistsDiff.html.twig', [
                    'decklist1' => [
                        'faction_code' => $d1->getFaction()->getCode(),
                        'name' => $d1->getName(),
                        'id' => $d1->getId(),
                        'prettyname' => $d1->getPrettyname(),
                        'content' => $content1
                    ],
                    'decklist2' => [
                        'faction_code' => $d2->getFaction()->getCode(),
                        'name' => $d2->getName(),
                        'id' => $d2->getId(),
                        'prettyname' => $d2->getPrettyname(),
                        'content' => $content2
                    ],
                    'shared' => $shared
                        ]
        );
    }

}
