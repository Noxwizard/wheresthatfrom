<?php

function lookup_mark($marks_db, $sphinx_db, $mark)
{
    $info = [];

    $escaped_mark = $sphinx_db->link->real_escape_string($mark);
    $result = $sphinx_db->query('select id, country, WEIGHT() as weight from marks_idx where MATCH(\'"^' . $escaped_mark . '$" | "' . $escaped_mark . '"\') ORDER BY weight DESC LIMIT 10');
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $info[$row['id']] = [
                'country'   => $row['country'],
                'weight'    => $row['weight'],
            ];
        }
        $result->close();
    }

    foreach ($info as $id => $data)
    {
        $sql = 'SELECT mark, serial, is_foreign, source FROM marks WHERE id = ' . (int) $id;
        $result = $marks_db->query($sql);
        if ($result)
        {
            $row = $result->fetch_assoc();
            $info[$id]['mark'] = $row['mark'];
            $info[$id]['serial'] = $row['serial'];
            $info[$id]['foreign'] = $row['is_foreign'];
            $info[$id]['source'] = $row['source'];

            $result->close();
        }
    }

    return $info;
}

function lookup_company($marks_db, $sphinx_db, $mark)
{
    $info = [];

    $escaped_mark = $sphinx_db->link->real_escape_string($mark);
    $result = $sphinx_db->query('select id, country, WEIGHT() as weight from companies_idx where MATCH(\'"^' . $escaped_mark . '$" | "' . $escaped_mark . '"\') ORDER BY weight DESC LIMIT 10');
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $info[$row['id']] = [
                'country'   => $row['country'],
                'weight'    => $row['weight'],
            ];
        }
        $result->close();
    }

    foreach ($info as $id => $data)
    {
        $sql = 'SELECT company, is_foreign, source FROM companies WHERE id = ' . (int) $id;
        $result = $marks_db->query($sql);
        if ($result)
        {
            $row = $result->fetch_assoc();
            $info[$id]['company'] = $row['company'];
            $info[$id]['foreign'] = $row['is_foreign'];
            $info[$id]['source'] = $row['source'];

            $result->close();
        }
    }

    return $info;
}

function output_json($data)
{
    if (!defined('IN_DEBUG'))
    {
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    die();
}