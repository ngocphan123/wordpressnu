<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate Thu, 17 Apr 2014 09:24:02 GMT
 */

if (!defined('NV_IS_FILE_ADMIN')) die('Stop!!!');
$module_data = 'news';

$viewcat = 'viewcat_page_new';

function nv_get_plaintext( $string, $keep_image = false, $keep_link = false )
{
    // Get image tags
    if( $keep_image )
    {
        if( preg_match_all( "/\<img[^\>]*src=\"([^\"]*)\"[^\>]*\>/is", $string, $match ) )
        {
            foreach( $match[0] as $key => $_m )
            {
                $textimg = '';
                if( strpos( $match[1][$key], 'data:image/png;base64' ) === false )
                {
                    $textimg = " " . $match[1][$key];
                }
                if( preg_match_all( "/\<img[^\>]*alt=\"([^\"]+)\"[^\>]*\>/is", $_m, $m_alt ) )
                {
                    $textimg .= " " . $m_alt[1][0];
                }
                $string = str_replace( $_m, $textimg, $string );
            }
        }
    }
    
    // Get link tags
    if( $keep_link )
    {
        if( preg_match_all( "/\<a[^\>]*href=\"([^\"]+)\"[^\>]*\>(.*)\<\/a\>/isU", $string, $match ) )
        {
            foreach( $match[0] as $key => $_m )
            {
                $string = str_replace( $_m, $match[1][$key] . " " . $match[2][$key], $string );
            }
        }
    }
    
    $string = str_replace( ' ', ' ', strip_tags( $string ) );
    return preg_replace( '/[ ]+/', ' ', $string );
}

function nv_fix_cat_order($parentid = 0, $order = 0, $lev = 0)
{
    global $db, $module_data;
    
    $sql = 'SELECT catid, parentid FROM ' . NV_PREFIXLANG . '_' . $module_data . '_cat WHERE parentid=' . $parentid . ' ORDER BY weight ASC';
    $result = $db->query($sql);
    $array_cat_order = array();
    while ($row = $result->fetch()) {
        $array_cat_order[] = $row['catid'];
    }
    $result->closeCursor();
    $weight = 0;
    if ($parentid > 0) {
        ++$lev;
    } else {
        $lev = 0;
    }
    foreach ($array_cat_order as $catid_i) {
        ++$order;
        ++$weight;
        $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET weight=' . $weight . ', sort=' . $order . ', lev=' . $lev . ' WHERE catid=' . intval($catid_i);
        $db->query($sql);
        $order = nv_fix_cat_order($catid_i, $order, $lev);
    }
    $numsubcat = $weight;
    if ($parentid > 0) {
        $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_cat SET numsubcat=' . $numsubcat;
        if ($numsubcat == 0) {
            $sql .= ",subcatid='', viewcat='viewcat_page_new'";
        } else {
            $sql .= ",subcatid='" . implode(',', $array_cat_order) . "'";
        }
        $sql .= ' WHERE catid=' . intval($parentid);
        $db->query($sql);
    }
    return $order;
}

function nv_news_get_bodytext($bodytext)
{
    // Get image tags
    if (preg_match_all("/\<img[^\>]*src=\"([^\"]*)\"[^\>]*\>/is", $bodytext, $match)) {
        foreach ($match[0] as $key => $_m) {
            $textimg = '';
            if (strpos($match[1][$key], 'data:image/png;base64') === false) {
                $textimg = " " . $match[1][$key];
            }
            if (preg_match_all("/\<img[^\>]*alt=\"([^\"]+)\"[^\>]*\>/is", $_m, $m_alt)) {
                $textimg .= " " . $m_alt[1][0];
            }
            $bodytext = str_replace($_m, $textimg, $bodytext);
        }
    }
    // Get link tags
    if (preg_match_all("/\<a[^\>]*href=\"([^\"]+)\"[^\>]*\>(.*)\<\/a\>/isU", $bodytext, $match)) {
        foreach ($match[0] as $key => $_m) {
            $bodytext = str_replace($_m, $match[1][$key] . " " . $match[2][$key], $bodytext);
        }
    }
    
    $bodytext = nv_unhtmlspecialchars(strip_tags($bodytext));
    $bodytext = str_replace('&nbsp;', ' ', $bodytext);
    return preg_replace('/[ ]+/', ' ', $bodytext);
}

function nv_create_table_news($lang, $module_data, $newcatid)
{
    global $db;
    $db->query("CREATE TABLE " . NV_PREFIXLANG . "_" . $module_data . "_" . $newcatid . "(
	 id int(11) unsigned NOT NULL auto_increment,
	 catid smallint(5) unsigned NOT NULL default '0',
	 listcatid varchar(255) NOT NULL default '',
	 topicid smallint(5) unsigned NOT NULL default '0',
	 admin_id mediumint(8) unsigned NOT NULL default '0',
	 author varchar(250) default '',
	 sourceid mediumint(8) NOT NULL default '0',
	 addtime int(11) unsigned NOT NULL default '0',
	 edittime int(11) unsigned NOT NULL default '0',
	 status tinyint(4) NOT NULL default '1',
	 publtime int(11) unsigned NOT NULL default '0',
	 exptime int(11) unsigned NOT NULL default '0',
	 archive tinyint(1) unsigned NOT NULL default '0',
	 title varchar(250) NOT NULL default '',
	 alias varchar(250) NOT NULL default '',
	 hometext text NOT NULL,
	 homeimgfile varchar(255) default '',
	 homeimgalt varchar(255) default '',
	 homeimgthumb tinyint(4) NOT NULL default '0',
	 inhome tinyint(1) unsigned NOT NULL default '0',
	 allowed_comm varchar(255) default '',
	 allowed_rating tinyint(1) unsigned NOT NULL default '0',
     external_link tinyint(1) unsigned NOT NULL default '0',
	 hitstotal mediumint(8) unsigned NOT NULL default '0',
	 hitscm mediumint(8) unsigned NOT NULL default '0',
	 total_rating int(11) NOT NULL default '0',
	 click_rating int(11) NOT NULL default '0',
	 instant_active tinyint(1) NOT NULL default '0',
     instant_template varchar(100) NOT NULL default '',
	 instant_creatauto tinyint(1) NOT NULL default '0',
	 PRIMARY KEY (id),
	 KEY catid (catid),
	 KEY topicid (topicid),
	 KEY admin_id (admin_id),
	 KEY author (author),
	 KEY title (title),
	 KEY addtime (addtime),
	 KEY edittime (edittime),
	 KEY publtime (publtime),
	 KEY exptime (exptime),
	 KEY status (status),
	 KEY instant_active (instant_active),
	 KEY instant_creatauto (instant_creatauto)
	) ENGINE=MyISAM");

}
function nv_create_table_cat($term_id ,$weight, $viewcat, $module_data, $name, $slug, $admin_id,$parent = 0, $description = '')
{
    global $db;
    $sql = "INSERT INTO " . NV_PREFIXLANG . "_" . $module_data . "_cat (catid, parentid, title, titlesite, alias, description, image, viewdescription, weight, sort, lev, viewcat, numsubcat, subcatid, inhome, numlinks, newday, keywords, admins, add_time, edit_time, groups_view) VALUES
			(" . $term_id. ", :parentid, :title, :titlesite, :alias, :description, '', '', :weight, '0', '0', :viewcat, '0', :subcatid, '1', '3', '2', :keywords, :admins, " . NV_CURRENTTIME . ", " . NV_CURRENTTIME . ", :groups_view)";
    
    $data_insert = array();
    $data_insert['parentid'] = $parent;
    $data_insert['title'] = $name;
    $data_insert['titlesite'] = $name;
    $data_insert['alias'] = $slug;
    $data_insert['description'] = $description;
    $data_insert['weight'] = $weight;
    $data_insert['viewcat'] = $viewcat;
    $data_insert['subcatid'] = '';
    $data_insert['keywords'] = '';
    $data_insert['admins'] = '';
    $data_insert['groups_view'] = '6';
    
    $newcatid = $db->insert_id($sql, 'catid', $data_insert);
    if ($newcatid > 0) {
        nv_create_table_news(NV_LANG_DATA, $module_data, $newcatid);
        
        if (!defined('NV_IS_ADMIN_MODULE')) {
            $db->query('INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_admins (userid, catid, admin, add_content, pub_content, edit_content, del_content) VALUES (' . $admin_id . ', ' . $newcatid . ', 1, 1, 1, 1, 1)');
        }
    } else {
        $error .= '<br>Error: ' . $data_insert['title'];
    }
}
//comment
if ($nv_Request->isset_request('commentsubmit', 'post')) {
    $db->query('TRUNCATE ' . NV_PREFIXLANG . '_comment');
    $_query = $db->query('SELECT * FROM `wp_comments` WHERE`comment_post_id` IN (SELECT id FROM nv4_vi_news_rows )');
    while ($row = $_query ->fetch()) {
        $db->query('INSERT INTO ' . NV_PREFIXLANG . '_comment(`cid`, `module`, `area`, `id`, `pid`, `content`, `post_time`, `userid`, `post_name`, `post_email`, `post_ip`, `status`, `likes`, `dislikes`) VALUES ('.$row['comment_id'].', '.$db->quote($module_data).', 8, '.$row['comment_post_id'].', 0, '.$db->quote($row['comment_content']).', '.$db->quote($row['comment_date']).', '.$row['user_id'].', '.$db->quote($row['comment_author']).', '.$db->quote($row['comment_author_email']).', '.$db->quote($row['comment_author_ip']).', 1,  0, 0)');
        
    }    
}

//tag
if ($nv_Request->isset_request('tagsubmit', 'post')) {
    $_query = $db->query("SELECT wp_terms.term_id, `name`, `slug`, `description`, `parent`, count FROM `wp_terms`, `wp_term_taxonomy` WHERE wp_terms.term_id = wp_term_taxonomy.term_id AND  wp_term_taxonomy.taxonomy='post_tag' ORDER BY `parent` ASC, term_id ASC");
    $aray_table_bodyhtml = array();
    while ($rowcontent = $_query->fetch()) {
        $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_tags (tid, numnews, alias, keywords, description) VALUES (:tid, :numnews, :alias, :keywords, :description)';
        //INSERT INTO `nv4_vi_news_tags`(`tid`, `numnews`, `alias`, `image`, `description`, `keywords`) VALUES ([value-1],[value-2],[value-3],[value-4],[value-5],[value-6])
        
        $data_insert = array();
        $data_insert['tid'] = $rowcontent['term_id'];
        $data_insert['numnews'] = $rowcontent['count'];
        $data_insert['alias'] = $rowcontent['slug'];
        $data_insert['keywords'] = $rowcontent['name'];
        $data_insert['description'] = $rowcontent['description'];
        $tid = $db->insert_id($sql, 'tid', $data_insert);
        if(!empty($tid)) {
            $result=$db->query('SELECT `object_id` FROM `wp_term_relationships` WHERE `term_taxonomy_id` IN (SELECT `term_taxonomy_id` FROM `wp_term_taxonomy` WHERE `term_id`='.$tid.') AND `object_id` IN (SELECT `id` FROM `nv4_vi_news_rows`)');
            while ($row = $result->fetch()) {
                $db->query('INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_tags_id(`id`, `tid`, `keyword`) VALUES ('.$row['object_id'].','.$tid.','.$db->quote($rowcontent['name']).')');
            }
        }      
    }
    
    
}
if ($nv_Request->isset_request('submit', 'post')) {
    // Set data for module News;
    $subcatid = '';
    $weight = 0;
    $admin_id = 0;
    try {
        $_query = $db->query("SELECT `catid` FROM `" . NV_PREFIXLANG . "_" . $module_data . "_cat` WHERE 1");
        while ($row = $_query->fetch()) {
            $db->query("DROP TABLE IF EXISTS `" . NV_PREFIXLANG . "_" . $module_data . "_" . $row['catid'] . "`");
        }
        
        $result = $db->query('SHOW TABLE STATUS LIKE ' . $db->quote($db_config['prefix'] . '\_' . $lang . '\_' . $module_data . '\_bodyhtml_%'));
        while ($item = $result->fetch()) {
            $db->query('DROP TABLE TRUNCATE ' . $item['name']);
        }
        
        $result = $db->query('SHOW TABLE STATUS LIKE ' . $db->quote(NV_PREFIXLANG . '\_' . $module_data . '\_%'));
        while ($item = $result->fetch()) {
            $db->query('TRUNCATE ' . $item['name']);
        }
        $db->query('DROP TABLE IF EXISTS ' . NV_PREFIXLANG . '_' . $module_data . '_bodytext');
        $db->query('CREATE TABLE ' . NV_PREFIXLANG . '_' . $module_data . '_bodytext (
	 id int(11) unsigned NOT NULL auto_increment,
	 bodyhtml longtext NOT NULL,
	 PRIMARY KEY (id)
	 ) ENGINE=MyISAM');
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    
    $error = 'OK';
    require_once NV_ROOTDIR . '/includes/action_' . $db->dbtype . '.php';
    try {
        $_query = $db->query("SELECT wp_terms.term_id, `name`, `slug`, `description`, `parent` FROM `wp_terms`, `wp_term_taxonomy` WHERE wp_terms.term_id = wp_term_taxonomy.term_id AND  wp_term_taxonomy.taxonomy='category' ORDER BY `parent` ASC, term_id ASC");
        while ($row = $_query->fetch()) {
            ++$weight;
            nv_create_table_cat($row['term_id'], $weight, $viewcat, $module_data, $row['name'],$row['slug'], $admin_id,$row['parent'], $row['description']);
        } 
        nv_create_table_cat(1500 ,$weight, $viewcat, $module_data, 'Tin Tức', change_alias('Tin Tức'), $admin_id,0, '');
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    
    if ($error == 'OK') {
        nv_fix_cat_order();
        $allowed_rating = 1;
        $hitstotal = 0;
        $hitscm = 0;
        $total_rating = 0;
        $click_rating = 0;
        
        $_query = $db->query("SELECT `id`, `post_author`, UNIX_TIMESTAMP(`post_date_gmt`) AS post_time , UNIX_TIMESTAMP(`post_modified_gmt`) AS post_edit ,`post_title`, `post_name` , `post_content`, guid, comment_count FROM `wp_posts` WHERE `post_type`='post' AND `post_status`='publish' ");
        
        $aray_table_bodyhtml = array();
        $homeimgfile = '';
        while ($rowcontent = $_query->fetch()) {
            $_array = $db->query("SELECT `term_id` FROM `wp_term_taxonomy` WHERE  `term_taxonomy_id` IN (SELECT `term_taxonomy_id` FROM `wp_term_relationships` WHERE `object_id`=" . $rowcontent['id'] . " ORDER BY `term_order` ASC) AND term_id in (SELECT catid FROM " . NV_PREFIXLANG . "_" . $module_data . "_cat)")->fetchAll();           
 
            $img = $db->query('SELECT guid FROM `wp_posts` WHERE`post_parent`='. $rowcontent['id'].' AND `post_type` = "attachment" LIMIT 1' )->fetchColumn();
           if(!empty($img)) {     
               $arr_img = explode("uploads",$img);
               $homeimgfile = $arr_img[1];
           }
           $hitstotal= $db->query('SELECT `pageviews` FROM `wp_popularpostsdata` WHERE `postid` = '. $rowcontent['id'])->fetchColumn();
           $hitscm = $db->query('SELECT COUNT(*) FROM `wp_comments` WHERE `comment_post_ID` = '. $rowcontent['id'])->fetchColumn();
           $catids = array();
            if (empty($_array)) {
                $catid = 1500;
                $catids[] = $catid;
            } else {
                foreach ($_array as $key => $_row_i) {
                    $catids[] = $_row_i[term_id];
                }
                $catid = $catids[0];
            }
            
            $allowed_comm = ($rowcontent['comment_status'] == 'open') ? 1 : 0;
            $bodyhtml = $nv_Request->security_post($rowcontent['post_content']);
            $bodyhtml = nv_nl2br($bodyhtml);
            
            $hometext = nv_substr($bodyhtml, 0);
            
            $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_rows
				(id, catid, listcatid, topicid, admin_id, author, sourceid, addtime, edittime, status, publtime, exptime, archive, title, alias, hometext, homeimgfile, homeimgalt, homeimgthumb, inhome, allowed_comm, allowed_rating, hitstotal, hitscm, total_rating, click_rating) VALUES
				 (
				 ' . intval($rowcontent['id']) . ',
				 ' . intval($catid) . ',
				 :listcatid,
				 0,
				 ' . intval($rowcontent['post_author']) . ',
				 :author,
				 0,
				 ' . intval($rowcontent['post_time']) . ',
				 ' . intval($rowcontent['post_edit']) . ',
				 1,
				 ' . intval($rowcontent['post_time']) . ',
				 0,
				 1,
				 :title,
				 :alias,
				 :hometext,
				 :homeimgfile,
				 :homeimgalt,
				 :homeimgthumb,
				 1,
				 ' . $allowed_comm . ',
				 ' . intval($allowed_rating) . ',
				 ' . intval($hitstotal) . ',
				 ' . intval($hitscm) . ',
				 ' . intval($total_rating) . ',
				 ' . intval($click_rating) . ')';
            
            $homeimgalt = '';
            $homeimgthumb = 2;
            $sourcetext = '';
            $hometext = nv_get_plaintext($hometext);
            $data_insert = array();
            $data_insert['listcatid'] = implode(',', $catids);
            $data_insert['author'] = '';
            $data_insert['title'] = $rowcontent['post_title'];
            $data_insert['alias'] = change_alias($rowcontent['post_name']);
            $data_insert['hometext'] = nv_clean60($hometext, 500, true);
            $data_insert['homeimgfile'] = $homeimgfile;
            $data_insert['homeimgalt'] = $homeimgalt;
            $data_insert['homeimgthumb'] = $homeimgthumb;
            
            $id = $db->insert_id($sql, 'id', $data_insert);
            if ($id == $rowcontent['id']) {
                
                //insert detail
                $stmt = $db->prepare('INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_detail (`id`, `titlesite`, `description`, `bodyhtml`, `sourcetext`, `imgposition`, `copyright`, `allowed_send`, `allowed_print`, `allowed_save`, `gid`) VALUES
					(' . $rowcontent['id'] . ',
					 " ",
					 " ",
					 ' . $db->quote($bodyhtml) . ',
					 ' . $db->quote($sourcetext) . ',
					 1,
					 1,
					 1,
					 1,
					 1,
					 0
					 )');
                $stmt->execute();
                
                $tbhtml = NV_PREFIXLANG . '_' . $module_data . '_bodyhtml_' . ceil($rowcontent['id'] / 2000);
                if (!in_array($tbhtml, $aray_table_bodyhtml)) {
                    $aray_table_bodyhtml[] = $tbhtml;
                    $db->query("CREATE TABLE IF NOT EXISTS " . $tbhtml . " (id int(11) unsigned NOT NULL, bodyhtml longtext NOT NULL, sourcetext varchar(255) NOT NULL default '', imgposition tinyint(1) NOT NULL default '1', copyright tinyint(1) NOT NULL default '0', allowed_send tinyint(1) NOT NULL default '0', allowed_print tinyint(1) NOT NULL default '0', allowed_save tinyint(1) NOT NULL default '0', gid mediumint(9) NOT NULL DEFAULT '0', PRIMARY KEY (id)) ENGINE=MyISAM");
                }
                
                $ct_query = array();
                
                $stmt = $db->prepare('INSERT INTO ' . $tbhtml . ' VALUES
					(' . $rowcontent['id'] . ',
					 :bodyhtml,
					 :sourcetext,
					 1,
					 0,
					 1,
					 1,
					 1,
					 0
					 )');
                
                $stmt->bindParam(':bodyhtml', $bodyhtml, PDO::PARAM_STR, strlen($bodyhtml));
                $stmt->bindParam(':sourcetext', $sourcetext, PDO::PARAM_STR, strlen($sourcetext));
                $ct_query[] = (int) $stmt->execute();
                
                foreach ($catids as $catid) {
                    $ct_query[] = (int) $db->exec('INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_' . $catid . ' SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE id=' . $rowcontent['id']);
                }
                
                $bodytext = nv_news_get_bodytext($bodyhtml);
                $stmt = $db->prepare('INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_bodytext VALUES (' . $rowcontent['id'] . ', :bodytext )');
                $stmt->bindParam(':bodytext', $bodytext, PDO::PARAM_STR, strlen($bodytext));
                $ct_query[] = (int) $stmt->execute();
                
                if (array_sum($ct_query) != sizeof($ct_query)) {
                    echo '<b>ERROR: ' . $rowcontent['post_title'] . '</b><br>';
                } else {
                    echo $rowcontent['post_title'] . '<br>';
                }
                unset($ct_query);
            } else {
                echo '<b>ERROR: ' . $rowcontent['post_title'] . '</b><br>';
            }
        }
    
    }
    die($error);
} else {
    $xtpl = new XTemplate($op . '.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('NV_LANG_VARIABLE', NV_LANG_VARIABLE);
    $xtpl->assign('NV_LANG_DATA', NV_LANG_DATA);
    $xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
    $xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
    $xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
    $xtpl->assign('MODULE_NAME', $module_name);
    $xtpl->assign('OP', $op);
    
    $xtpl->parse('main');
    $contents = $xtpl->text('main');
}

$page_title = $lang_module['main'];

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';