<?php
include('config.php');

if (!defined('IN_DEBUG'))
{
    ini_set('display_errors', false);
}

include('db.php');
include('functions.php');

$output = [];
if (!isset($_POST['keyword']) || empty($_POST['keyword']) || is_array($_POST['keyword']))
{
    output_json([]);
}


try
{
    $mysql = new db(DB_HOST, DB_PORT, DB_DB, DB_USER, DB_PASS);
    $sphinx = new db(SPHINX_HOST, SPHINX_PORT, NULL, NULL, NULL);
}
catch (Exception $e)
{
    output_json(['error' => 'Error connecting to database: ' . $e->getMessage() ]);
}


//
// See if there's a trademark
//
$mark = $_POST['keyword'];
$mark_info = lookup_mark($mysql, $sphinx, $mark);

// Get companies for each trademark
foreach ($mark_info as $mark_id => $data)
{
    $sql = 'SELECT c.company, c.is_foreign, c.country, c.source FROM companies AS c
            JOIN mark_to_company AS map ON (map.company_id = c.id)
            WHERE map.mark_id = ' . (int) $mark_id;
    $result = $mysql->query($sql);
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $mark_info[$mark_id]['companies'][] = $row;
        }
        $result->close();
    }
}

// See if there was an exact match
$exact = false;
foreach ($mark_info as $mark_id => $data)
{
    // Near-matches return a weight of 10000. Exact matches are higher
    if ((int) $data['weight'] > 10000)
    {
        $exact = $mark_id;
        break;
    }
}

if ($exact !== false)
{
    // We have an exact match, now check foreignness of entry and all associated countries
    $is_foreign = $mark_info[$exact]['foreign'];
    $info_source = $mark_info[$exact]['source'];
    $country = $mark_info[$exact]['country'];
    if (!$is_foreign)
    {
        foreach ($mark_info[$exact]['companies'] as $company)
        {
            if ($company['is_foreign'])
            {
                $is_foreign = true;
                $info_source = $company['source'];
                $country = $company['country'];
                break;
            }
        }
    }

    // Get source URL
    $source_url = '';
    $sql = 'SELECT url FROM sources WHERE id = ' . (int) $info_source;
    $result = $mysql->query($sql);
    if ($result)
    {
        $source_url = $result->fetch_assoc()['url'];
        $result->close();
    }

    $output['mark_info'] = [
        'mark'          => $mark_info[$exact]['mark'],
        'is_foreign'    => $is_foreign,
        'country'       => $country,
        'source'        => $source_url,
        'serial'        => $mark_info[$exact]['serial'],
    ];

    output_json($output);
}
unset($mark_info);
// No exact trademark match


//
// See if there's a company by that name
//
$company_info = lookup_company($mysql, $sphinx, $mark);

// See if there was an exact match
$exact = false;
foreach ($company_info as $company_id => $data)
{
    // Near-matches return a weight of 10000. Exact matches are higher
    if ((int) $data['weight'] > 10000)
    {
        $exact = $company_id;
        break;
    }
}

if ($exact !== false)
{
    $is_foreign = $company_info[$exact]['foreign'];
    $info_source = $company_info[$exact]['source'];
    $country = $company_info[$exact]['country'];

    // Get source URL
    $source_url = '';
    $sql = 'SELECT url FROM sources WHERE id = ' . (int) $info_source;
    $result = $mysql->query($sql);
    if ($result)
    {
        $source_url = $result->fetch_assoc()['url'];
        $result->close();
    }

    $output['company_info'] = [
        'company'       => $company_info[$exact]['company'],
        'is_foreign'    => $is_foreign,
        'country'       => $country,
        'source'        => $source_url,
    ];

    output_json($output);
}

output_json([]);