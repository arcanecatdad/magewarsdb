<?php


namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;

class Reviews
{
    public function __construct(EntityManager $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    public function recent($start = 0, $limit = 30)
    {
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->doctrine->getConnection();
    
        $rows = $dbh->executeQuery(
                "SELECT SQL_CALC_FOUND_ROWS
                r.id,
                r.date_creation,
                r.text,
                r.rawtext,
                r.nbvotes,
                c.id card_id,
                c.title card_title,
                c.code card_code,
                p.name pack_name,
                u.id user_id,
                u.username,
                u.faction usercolor,
                u.reputation,
                u.donation
                from review r
                join user u on r.user_id=u.id
                join card c on r.card_id=c.id
                join pack p on c.pack_id=p.id
                where r.date_creation > DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
        		and p.date_release is not null
                order by r.date_creation desc
                limit $start, $limit")->fetchAll(\PDO::FETCH_ASSOC);
    
        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];
    
        return array(
                "count" => $count,
                "reviews" => $rows
        );
    
    }

    public function by_author($user_id, $start = 0, $limit = 30)
    {
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->doctrine->getConnection();
    
        $rows = $dbh->executeQuery(
                "SELECT SQL_CALC_FOUND_ROWS
                r.id,
                r.date_creation,
                r.text,
                r.rawtext,
                r.nbvotes,
                c.id card_id,
                c.title card_title,
                c.code card_code,
                p.name pack_name,
                u.id user_id,
                u.username,
                u.faction usercolor,
                u.reputation,
                u.donation
                from review r
                join user u on r.user_id=u.id
                join card c on r.card_id=c.id
                join pack p on c.pack_id=p.id
                where r.user_id=?
        		and p.date_release is not null
        		order by c.code asc
                limit $start, $limit", array(
                        $user_id
                ))->fetchAll(\PDO::FETCH_ASSOC);
    
        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];
    
        return array(
                "count" => $count,
                "reviews" => $rows
        );
    
    }
}
