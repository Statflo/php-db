<?php

namespace Statflo\DB;

class Connection extends \Doctrine\DBAL\Connection
{
    public function getSchema()
    {
        $params = $this->getParams();

        if (isset($params['schema'])) {
            return $params['schema'];
        }

        return $this->getDatabase();
    }
}
