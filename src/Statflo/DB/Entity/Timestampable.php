<?php

namespace Statflo\DB\Entity;

trait Timestampable
{
    public function onCreate()
    {
        $date = new \DateTime("now", new \DateTimeZone('UTC'));

        $this->created_at = $date;
        $this->updated_at = $date;
    }

    public function onUpdate()
    {
        $this->updated_at = new \DateTime("now", new \DateTimeZone('UTC'));
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
