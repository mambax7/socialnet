<?php
//  ------------------------------------------------------------------------ //
//                      SOCIALNET - MODULE FOR XOOPS 2                       //
//                  Copyright (c) 2009-2010  David Yanez Osses               //
//                     <http://www.ipwgc.com/>                      		 //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

include 'header.php';
include_once XOOPS_ROOT_PATH."/modules/socialnet/class/socialnet_forumkeyhighlighter.class.php";
$forum = isset($_GET['forum']) ? intval($_GET['forum']) : 0;
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ( empty($forum) ) {
	redirect_header('forumstart.php',2,_MD_SOCIALNET_FORUM_ERRORFORUM);
	exit();
} elseif ( empty($topic_id) ) {
	redirect_header('forumview.php?forum='.$forum,2,_MD_SOCIALNET_FORUM_ERRORTOPIC);
	exit();
}
$topic_time = (isset($_GET['topic_time'])) ? intval($_GET['topic_time']) : 0;
$post_id = !empty($_GET['post_id']) ? intval($_GET['post_id']) : 0;

//use users preferences
if (is_object($xoopsUser)) {
	$viewmode = $xoopsUser->getVar('umode');
	$order = ($xoopsUser->getVar('uorder') == 1) ? 'DESC' : 'ASC';
} else {
	$viewmode = 'flat';
	$order = 'ASC';
}

$myts =& MyTextSanitizer::getInstance();
// socialnet does not have nested mode
if ($viewmode == 'nest') {
	$viewmode = 'thread';
}

// override mode/order if any requested
if (isset($_GET['viewmode']) && ($_GET['viewmode'] == 'flat' || $_GET['viewmode'] == 'thread')) {
	$viewmode = $_GET['viewmode'];
}
if (isset($_GET['order']) && ($_GET['order'] == 'ASC' || $_GET['order'] == 'DESC')) {
	$order = $_GET['order'];
}

if ($viewmode != 'flat') {
	$xoopsOption['template_main'] =  'socialnet_forumviewtopicthread.html';
} else {
	$xoopsOption['template_main'] =  'socialnet_forumviewtopicflat.html';
}

include XOOPS_ROOT_PATH.'/header.php';
include_once 'class/socialnet_forumposts.class.php';
require_once XOOPS_ROOT_PATH.'/modules/socialnet/class/socialnet_forumsfiles.class.php';

if ( isset($_GET['move']) && 'next' == $_GET['move'] ) {
	$sql = 'SELECT t.topic_id, t.topic_title, t.topic_time, t.topic_status, t.topic_sticky, t.topic_last_post_id, f.forum_id, f.forum_name, f.forum_access, f.forum_type, f.allow_html, f.allow_sig, f.posts_per_page, f.hot_threshold, f.topics_per_page, f.allow_upload FROM '.$xoopsDB->prefix('socialnet_forumtopics').' t LEFT JOIN '.$xoopsDB->prefix('socialnet_forums').' f ON f.forum_id = t.forum_id WHERE t.topic_time > '.$topic_time.' AND t.forum_id = '.$forum.' ORDER BY t.topic_time ASC LIMIT 1';
} elseif ( isset($_GET['move']) && 'prev' == $_GET['move']) {
	$sql = 'SELECT t.topic_id, t.topic_title, t.topic_time, t.topic_status, t.topic_sticky, t.topic_last_post_id, f.forum_id, f.forum_name, f.forum_access, f.forum_type, f.allow_html, f.allow_sig, f.posts_per_page, f.hot_threshold, f.topics_per_page, f.allow_upload FROM '.$xoopsDB->prefix('socialnet_forumtopics').' t LEFT JOIN '.$xoopsDB->prefix('socialnet_forums').' f ON f.forum_id = t.forum_id WHERE t.topic_time < '.$topic_time.' AND t.forum_id = '.$forum.' ORDER BY t.topic_time DESC LIMIT 1';
} else {
	$sql = 'SELECT t.topic_id, t.topic_title, t.topic_time, t.topic_status, t.topic_sticky, t.topic_last_post_id, f.forum_id, f.forum_name, f.forum_access, f.forum_type, f.allow_html, f.allow_sig, f.posts_per_page, f.hot_threshold, f.topics_per_page, f.allow_upload FROM '.$xoopsDB->prefix('socialnet_forumtopics').' t LEFT JOIN '.$xoopsDB->prefix('socialnet_forums').' f ON f.forum_id = t.forum_id WHERE t.topic_id = '.$topic_id.' AND t.forum_id = '.$forum;
}

if ( !$result = $xoopsDB->query($sql) ) {
	redirect_header('forumview.php?forum='.$forum,2,_MD_SOCIALNET_FORUM_ERROROCCURED);
	exit();
}

if ( !$forumdata = $xoopsDB->fetchArray($result) ) {
	redirect_header('forumview.php?forum='.$forum,2,_MD_SOCIALNET_FORUM_FORUMNOEXIST);
	exit();
}

$xoopsTpl->assign('topic_id', $forumdata['topic_id']);
$topic_id = $forumdata['topic_id'];
$xoopsTpl->assign('forum_id', $forumdata['forum_id']);
$forum = $forumdata['forum_id'];
$can_post = 0;
$show_reg = 0;
if ( $forumdata['forum_type'] == 1 ) {
	// this is a private forum.
	$accesserror = 0;
	if ( is_object($xoopsUser) ) {
		if ( !$xoopsUser->isAdmin($xoopsModule->mid()) ) {
			if ( !check_priv_forum_auth($xoopsUser->getVar('uid'), $forum, false) ) {
				$accesserror = 1;
			}
		} else {
			$isadminormod = 1;
		}
	} else {
		$accesserror = 1;
	}
	if ( $accesserror == 1 ) {
		redirect_header("forumstart.php",3, _MD_SOCIALNET_FORUM_NORIGHTTOACCESS);
		exit();
	}
	$can_post = 1;
	$show_reg = 1;
} else {
	// this is not a priv forum
	if ( $forumdata['forum_access'] == 1 ) {
		// this is a reg user only forum
		if ( is_object($xoopsUser)) {
			$can_post = 1;
		} else {
			$show_reg = 1;
		}
	} elseif ( $forumdata['forum_access'] == 2 ) {
		// this is an open forum
		$can_post = 1;
	} else {
		// this is an admin/moderator only forum
		if ( is_object($xoopsUser)) {
			if ( $xoopsUser->isAdmin($xoopsModule->mid()) || is_moderator($forum, $xoopsUser->getVar('uid')) ) {
				$can_post = 1;
				$isadminormod = 1;
			}
		}
	}
}
$myts =& MyTextSanitizer::getInstance();
$forumdata['topic_title'] = $myts->htmlSpecialChars($forumdata['topic_title']);
$forumdata['forum_name'] = $myts->htmlSpecialChars($forumdata['forum_name']);
$xoopsTpl->assign(array('topic_title' => '<a href="'.$bbUrl['root'].'forumviewtopic.php?viewmode='.$viewmode.'&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'">'.$forumdata['topic_title'].'</a>', 'forum_name' => $forumdata['forum_name'], 'topic_time' => $forumdata['topic_time'], 'lang_nexttopic' => _MD_SOCIALNET_FORUM_NEXTTOPIC, 'lang_prevtopic' => _MD_SOCIALNET_FORUM_PREVTOPIC));

// add image links to admin page if the user viewing this page is a forum admin
if ( is_object($xoopsUser)) {
	$xoopsTpl->assign('viewer_userid', $xoopsUser->getVar('uid'));
	if ( !empty($isadminormod) || $xoopsUser->isAdmin($xoopsModule->mid()) || is_moderator( $forum, $xoopsUser->getVar('uid')) ) {
		// yup, the user is admin
		// the forum is locked?
		if ( $forumdata['topic_status'] != 1 ) {
			// nope
			$xoopsTpl->assign('topic_lock_image', '<a href="'.$bbUrl['root'].'forumtopicmanager.php?mode=lock&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'"><img src="'.$bbImage['locktopic'].'" alt="'._MD_SOCIALNET_FORUM_LOCKTOPIC.'" /></a>');
		} else {
			// yup, it is..
			$xoopsTpl->assign('topic_lock_image', '<a href="'.$bbUrl['root'].'forumtopicmanager.php?mode=unlock&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'"><img src="'.$bbImage['unlocktopic'].'" alt="'._MD_SOCIALNET_FORUM_UNLOCKTOPIC.'" /></a>');
		}
		$xoopsTpl->assign('topic_move_image', '<a href="'.$bbUrl['root'].'forumtopicmanager.php?mode=move&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'"><img src="'.$bbImage['movetopic'].'" alt="'._MD_SOCIALNET_FORUM_MOVETOPIC.'" /></a>');
		$xoopsTpl->assign('topic_delete_image', '<a href="'.$bbUrl['root'].'forumtopicmanager.php?mode=del&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'"><img src="'.$bbImage['deltopic'].'" alt="'._MD_SOCIALNET_FORUM_DELETETOPIC.'" /></a>');
		// is the topic sticky?
		if ( $forumdata['topic_sticky'] != 1 ) {
			// nope, not yet..
			$xoopsTpl->assign('topic_sticky_image', '<a href="'.$bbUrl['root'].'forumtopicmanager.php?mode=sticky&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'"><img src="'.$bbImage['sticky'].'" alt="'._MD_SOCIALNET_FORUM_STICKYTOPIC.'" /></a>');
		} else {
			// yup it is sticking..
			$xoopsTpl->assign('topic_sticky_image', '<a href="'.$bbUrl['root'].'forumtopicmanager.php?mode=unsticky&amp;topic_id='.$topic_id.'&amp;forum='.$forum.'"><img src="'.$bbImage['unsticky'].'" alt="'._MD_SOCIALNET_FORUM_UNSTICKYTOPIC.'" /></a>');
		}
		// need to set this also
		$xoopsTpl->assign('viewer_is_admin', true);
	} else {
		// nope, the user is not a forum admin..
		$xoopsTpl->assign('viewer_is_admin', false);
	}
} else {
	// nope, the user is not a forum admin, not even registered
	$xoopsTpl->assign(array('viewer_is_admin' => false, 'viewer_userid' => 0));
}

function showTree(&$arr, $current=0, $key=0, $prefix='', $foundusers=array()){
	global $xoopsConfig;
	global $forum;	//
	if ($key != 0) {
		if ( 0 != $arr[$key]['obj']->uid() ) {
			if (!isset($foundusers[$arr[$key]['obj']->uid()])) {
				$eachposter = new XoopsUser($arr[$key]['obj']->uid());
				$foundusers[$arr[$key]['obj']->uid()] =& $eachposter;
			} else {
				$eachposter =& $foundusers[$arr[$key]['obj']->uid()];
			}
			$poster_rank = $eachposter->rank();
			if ( $poster_rank['image'] != '' ) {
				$poster_rank['image'] = '<img src="'.XOOPS_UPLOAD_URL.'/'.$poster_rank['image'].'" alt="" />';
			}
			if ( $eachposter->isActive() ) {
				//
				$show_name_username=$eachposter->getVar('uname');
				if(get_show_name($forum)) {
					if(trim(username($eachposter->getVar('uid')))!='') {
						$show_name_username=username($eachposter->getVar('uid'));
					}
				}
				$posterarr =  array('poster_uid' => $eachposter->getVar('uid'), 'poster_uname' => '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$eachposter->getVar('uid').'">'.$eachposter->getVar('uname').'</a>');
			} else {
				$posterarr = array('poster_uid' =>0, 'poster_uname' => $xoopsConfig['anonymous']);
			}
		} else {
			$posterarr = array('poster_uid' =>0, 'poster_uname' => $xoopsConfig['anonymous']);
		}
		$posticon = $arr[$key]['obj']->icon();
		if ( isset($posticon) && $posticon != '' ) {
			$post_image = '<img src="'.XOOPS_URL.'/images/subject/'.htmlspecialchars( $posticon, ENT_QUOTES ).'" alt="" />';
		} else {
			$post_image =  '<img src="'.XOOPS_URL.'/images/icons/no_posticon.gif" alt="" />';
		}
		if ($current != $key) {
			$subject = '<a href="forumviewtopic.php?viewmode=thread&amp;topic_id='.$arr[$key]['obj']->topic().'&amp;forum='.$arr[$key]['obj']->forum().'&amp;post_id='.$arr[$key]['obj']->postid().'#'.$arr[$key]['obj']->postid().'">'.$arr[$key]['obj']->subject().'</a>';
			$GLOBALS['xoopsTpl']->append("topic_trees", array_merge($posterarr, array("post_id" => $arr[$key]['obj']->postid(), "post_parent_id" => $arr[$key]['obj']->parent(), "post_date" => formatTimestamp($arr[$key]['obj']->posttime(), "m"), "post_image" => $post_image, "post_title" => $subject, "post_prefix" => $prefix)));
		} else {
			$subject = '<b>'.$arr[$key]['obj']->subject().'</b>';
			$thisprefix = substr($prefix, 0, -6)."<b>&raquo;</b>";
			$GLOBALS['xoopsTpl']->append("topic_trees", array_merge($posterarr, array("post_id" => $arr[$key]['obj']->postid(), "post_parent_id" => $arr[$key]['obj']->parent(), "post_date" => formatTimestamp($arr[$key]['obj']->posttime(), "m"), "post_image" => $post_image, "post_title" => $subject, "post_prefix" => $thisprefix)));
		}
	}
	if ( isset($arr[$key]['replies']) && !empty($arr[$key]['replies']) ){
		$prefix .= "&nbsp;&nbsp;";
		foreach($arr[$key]['replies'] as $replykey) {
			$current = ( $current == 0 ) ? $replykey : $current;
			showTree($arr, $current, $replykey, $prefix, $foundusers);
		}
	}
}

if ($order == 'DESC') {
	$xoopsTpl->assign(array('order_current' => 'DESC', 'order_other' => 'ASC', 'lang_order_other' => _OLDESTFIRST));
} else {
	$xoopsTpl->assign(array('order_current' => 'ASC', 'order_other' => 'DESC', 'lang_order_other' => _NEWESTFIRST));
}

// initialize the start number of select query
$start = !empty($_GET['start']) ? intval($_GET['start']) : 0;

$total_posts = get_total_postsex($topic_id, 'topic');
if ($total_posts > 50) {
	$viewmode ="flat";
	// hide link to theaded view
	$xoopsTpl->assign('lang_threaded', "" );
	$xoopsTpl->assign('lang_flat', _FLAT );
} else {
	$xoopsTpl->assign(array('lang_threaded' => _THREADED, 'lang_flat' => _FLAT));
}

if ( $can_post == 1 ) {
	$xoopsTpl->assign(array('viewer_can_post' => true, 'forum_post_or_register' => "<a href=\"forumnewtopic.php?forum=".$forum."\"><img src=\"".$bbImage['post']."\" alt=\""._MD_SOCIALNET_FORUM_POSTNEW."\" /></a>"));
} else {
	$xoopsTpl->assign('viewer_can_post', false);
	if ( $show_reg == 1 ) {
		$xoopsTpl->assign('forum_post_or_register', '<a href="'.XOOPS_URL.'/user.php?xoops_redirect='.htmlspecialchars($xoopsRequestUri).'">'._MD_SOCIALNET_FORUM_REGTOPOST.'</a>');
	} else {
		$xoopsTpl->assign('forum_post_or_register', '');
	}
}

// ***********************************************************************************************************************************************************
function my_highlighter ($matches) {
	$color='#FFFF80';
	if(substr($color,0,1)!='#') {
		$color='#'.$color;
	}
	return '<span style="font-weight: bolder; background-color: '.$color.';">' . $matches[0] . '</span>';
}
// ***********************************************************************************************************************************************************

$firsttitle='';
$firstcontent='';
if ( $viewmode == "thread" ) {
	$start = 0;
	$postsArray = ForumPosts::getAllPosts($topic_id, "ASC", $total_posts, $start);
	$xoopsTpl->assign('topic_viewmode', 'thread');

	$newObjArr = array();
	foreach ( $postsArray as $eachpost ) {
		$key1 = $eachpost->postid();
		if ( (!empty($post_id) && $post_id == $key1) || ( empty($post_id) && $eachpost->parent() == 0 ) ) {
			$post_text = $eachpost->text();
			if ( 0 != $eachpost->uid() ) {
				$eachposter = new XoopsUser($eachpost->uid());
				$poster_rank = $eachposter->rank();
				if ( $poster_rank['image'] != "" ) {
					$poster_rank['image'] = "<img src='".XOOPS_UPLOAD_URL."/".$poster_rank['image']."' alt='' />";
				}
				if ( $eachposter->isActive() ) {
					$poster_status = $eachposter->isOnline() ? _MD_SOCIALNET_FORUM_ONLINE : '';
					//
					$show_name_username=$eachposter->getVar('uname');
					if(get_show_name($forum)) {
						if(trim(username($eachposter->getVar('uid')))!='') {
							$show_name_username=username($eachposter->getVar('uid'));
						}
					}
					$posterarr =  array('poster_uid' => $eachposter->getVar('uid'), 'poster_uname' => '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$eachposter->getVar('uid').'">'.$eachposter->getVar('uname').'</a>', 'poster_avatar' => $eachposter->getVar('user_avatar'), 'poster_from' => $eachposter->getVar('user_from'), 'poster_regdate' => formatTimestamp($eachposter->getVar('user_regdate'), 's'), 'poster_postnum' => $eachposter->getVar('posts'), 'poster_sendpmtext' => sprintf(_SENDPMTO,$eachposter->getVar('uname')), 'poster_rank_title' => $poster_rank['title'], 'poster_rank_image' => $poster_rank['image'], 'poster_status' => $poster_status);
					if ( 1 == $forumdata['allow_sig'] && $eachpost->attachsig() == 1 && $eachposter->attachsig() == 1 ) {
						$myts =& MytextSanitizer::getInstance();
						$post_text .= "<p><br />----------------<br />". $myts->displayTarea($eachposter->getVar("user_sig", "N"), 0, 1, 1)."</p>";
					}
				} else {
					$posterarr = array('poster_uid' =>0, 'poster_uname' => $xoopsConfig['anonymous'], 'poster_avatar' => '', 'poster_from' => '', 'poster_regdate' => '', 'poster_postnum' => '', 'poster_sendpmtext' => '', 'poster_rank_title' => '', 'poster_rank_image' => '');
				}
			} else {
				$posterarr = array('poster_uid' =>0, 'poster_uname' => $xoopsConfig['anonymous'], 'poster_avatar' => '', 'poster_from' => '', 'poster_regdate' => '', 'poster_postnum' => '', 'poster_sendpmtext' => '', 'poster_rank_title' => '', 'poster_rank_image' => '');
			}
			$posticon = $eachpost->icon();
			if ( isset($posticon) && $posticon != '' ) {
				$post_image = '<a name="'.$eachpost->postid().'"><img src="'.XOOPS_URL.'/images/subject/'.htmlspecialchars( $posticon, ENT_QUOTES ).'" alt="" /></a>';
			} else {
				$post_image =  '<a name="'.$eachpost->postid().'"><img src="'.XOOPS_URL.'/images/icons/posticon.gif" alt="" /></a>';
			}
			// ***********************************************************************************************************************************************************
			if(isset($_GET['keywords'])) {
				$keywords=$myts->htmlSpecialChars(trim(urldecode($_GET['keywords'])));
				$h= new keyhighlighter ($keywords, true , 'my_highlighter');
				$post_text = $h->highlight($post_text);
			}
			// ***********************************************************************************************************************************************************
			// Manage uploads
			$topicFiles = array();
			if($forumdata['allow_upload'] == 1) {
				$sfiles = new socialnet_sFiles();
				$uploadedFiles = array();
				$uploadedFiles = $sfiles->getAllbyPost($eachpost->postid());
				if(count($uploadedFiles) > 0) {
					$topicFiles = array();
					foreach($uploadedFiles as $uploadedFile) {
						$topicFiles[] = array('fileid' => $uploadedFile->getFileid(), 'counter' => $uploadedFile->getCounter(), 'date' => formatTimestamp($uploadedFile->getDate(), 's'), 'filerealname' => $uploadedFile->getFileRealName(), 'mimetype' => $uploadedFile->getMimetype(), 'downloadname' => $uploadedFile->getDownloadname());
					}
				}
				unset($sfiles);
			}

			$xoopsTpl->append('topic_posts', array_merge($posterarr, array('post_id' => $eachpost->postid(), 'post_files' => $topicFiles, 'post_parent_id' => $eachpost->parent(), 'post_date' => formatTimestamp($eachpost->posttime(), 'm'), 'post_poster_ip'=> $eachpost->posterip(), 'post_image' => $post_image, 'post_title' => $eachpost->subject(), 'post_text' => $post_text)));
			if($firsttitle=='') {
				$firsttitle=$eachpost->subject();
				$firstcontent=$post_text;
			}
		}
		$newObjArr[$key1]['obj'] = $eachpost;
		$key2 = $eachpost->parent();
		$newObjArr[$key2]['replies'][] = $key1;
		$newObjArr[$key2]['leaf'] = $key1;
	}
	showTree($newObjArr, $post_id);
	$xoopsTpl->assign(array('lang_subject' => _MD_SOCIALNET_FORUM_SUBJECT, 'lang_date' => _MD_SOCIALNET_FORUM_DATE));
} else {
	$xoopsTpl->assign(array('topic_viewmode' => 'flat', 'lang_top', _MD_SOCIALNET_FORUM_TOP, 'lang_subject' => _MD_SOCIALNET_FORUM_SUBJECT, 'lang_bottom' => _MD_SOCIALNET_FORUM_BOTTOM));
	$postsArray = ForumPosts::getAllPosts($topic_id, $order, $forumdata['posts_per_page'], $start, $post_id);
	$foundusers = array();
	foreach ( $postsArray as $eachpost ) {
		$post_text = $eachpost->text();
		if ( 0 != $eachpost->uid() ) {
			if (!isset($foundusers['user'.$eachpost->uid()])) {
				$eachposter = new XoopsUser($eachpost->uid());
				$foundusers['user'.$eachpost->uid()] =& $eachposter;
			} else {
				$eachposter =& $foundusers['user'.$eachpost->uid()];
			}
			$poster_rank = $eachposter->rank();
			if ( $poster_rank['image'] != '' ) {
				$poster_rank['image'] = '<img src="'.XOOPS_UPLOAD_URL.'/'.$poster_rank['image'].'" alt="" />';
			}
			if ( $eachposter->isActive() ) {
				$poster_status = $eachposter->isOnline() ? _MD_SOCIALNET_FORUM_ONLINE : '';
				//
				$show_name_username=$eachposter->getVar('uname');
				if(get_show_name($forum))
				{
					if(trim(username($eachposter->getVar('uid')))!='')
					{
						$show_name_username=username($eachposter->getVar('uid'));
					}
				}
				$posterarr =  array('poster_uid' => $eachposter->getVar('uid'), 'poster_uname' => '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$eachposter->getVar('uid').'">'.$eachposter->getVar('uname').'</a>', 'poster_avatar' => $eachposter->getVar('user_avatar'), 'poster_from' => $eachposter->getVar('user_from'), 'poster_regdate' => formatTimestamp($eachposter->getVar('user_regdate'), 's'), 'poster_postnum' => $eachposter->getVar('posts'), 'poster_sendpmtext' => sprintf(_SENDPMTO,$eachposter->getVar('uname')), 'poster_rank_title' => $poster_rank['title'], 'poster_rank_image' => $poster_rank['image'], 'poster_status' => $poster_status);
				if ( 1 == $forumdata['allow_sig'] && $eachpost->attachsig() == 1 && $eachposter->attachsig() == 1 ) {
					$myts =& MytextSanitizer::getInstance();
					$post_text .= '<p><br />----------------<br />'. $myts->displayTarea($eachposter->getVar('user_sig', 'N'), 0, 1, 1).'</p>';
				}
			} else {
				$posterarr = array('poster_uid' =>0, 'poster_uname' => $xoopsConfig['anonymous'], 'poster_avatar' => '', 'poster_from' => '', 'poster_regdate' => '', 'poster_postnum' => '', 'poster_sendpmtext' => '', 'poster_rank_title' => '', 'poster_rank_image' => '');
			}
		} else {
			$posterarr = array('poster_uid' =>0, 'poster_uname' => $xoopsConfig['anonymous'], 'poster_avatar' => '', 'poster_from' => '', 'poster_regdate' => '', 'poster_postnum' => '', 'poster_sendpmtext' => '', 'poster_rank_title' => '', 'poster_rank_image' => '');
		}
		$posticon = $eachpost->icon();
		if ( isset($posticon) && $posticon != '' ) {
			$post_image = '<a name="'.$eachpost->postid().'"><img src="'.XOOPS_URL.'/images/subject/'.$eachpost->icon().'" alt="" /></a>';
		} else {
			$post_image =  '<a name="'.$eachpost->postid().'"><img src="'.XOOPS_URL.'/images/icons/no_posticon.gif" alt="" /></a>';
		}
		// ***********************************************************************************************************************************************************
		if(isset($_GET['keywords'])) {
			$keywords=$myts->htmlSpecialChars(trim(urldecode($_GET['keywords'])));
			$h= new keyhighlighter ($keywords, true , 'my_highlighter');
			$post_text = $h->highlight($post_text);
		}
		// ***********************************************************************************************************************************************************

		// Manage uploads
		$topicFiles = array();
		if($forumdata['allow_upload'] == 1) {
			$sfiles = new socialnet_sFiles();
			$uploadedFiles = array();
			$uploadedFiles = $sfiles->getAllbyPost($eachpost->postid());
			if(count($uploadedFiles) > 0) {
				$topicFiles = array();
				foreach($uploadedFiles as $uploadedFile) {
					$topicFiles[] = array('fileid' => $uploadedFile->getFileid(), 'counter' => $uploadedFile->getCounter(), 'date' => formatTimestamp($uploadedFile->getDate(), 's'), 'filerealname' => $uploadedFile->getFileRealName(), 'mimetype' => $uploadedFile->getMimetype(), 'downloadname' => $uploadedFile->getDownloadname());
				}
			}
			unset($sfiles);
		}

		$xoopsTpl->append('topic_posts', array_merge($posterarr, array('post_id' => $eachpost->postid(), 'post_files' => $topicFiles, 'post_parent_id' => $eachpost->parent(), 'post_date' => formatTimestamp($eachpost->posttime(), 'm'), 'post_poster_ip'=> $eachpost->posterip(), 'post_image' => $post_image, 'post_title' => $eachpost->subject(), 'post_text' => $post_text)));
		if($firsttitle=='') {
			$firsttitle=$eachpost->subject();
			$firstcontent=$post_text;
		}
		unset($eachposter);
	}
	if ( $total_posts > $forumdata['posts_per_page'] ) {
		include XOOPS_ROOT_PATH.'/class/pagenav.php';
		$nav = new XoopsPageNav($total_posts, $forumdata['posts_per_page'], $start, "start", 'topic_id='.$topic_id.'&amp;forum='.$forum.'&amp;viewmode='.$viewmode.'&amp;order='.$order);
		$xoopsTpl->assign('forum_page_nav', $nav->renderNav(4));
	} else {
		$xoopsTpl->assign('forum_page_nav', '');
	}
}

$xoopsTpl->assign('xoops_pagetitle', strip_tags($xoopsModule->name()) . ' - ' . strip_tags($myts->displayTarea($forumdata['forum_name'])) . ' - ' . strip_tags($myts->displayTarea($forumdata['topic_title'])));

// CHECK THIS PROBLEM LINE 439, 440, 442
//include XOOPS_ROOT_PATH.'/modules/socialnet/include/forumfunctions.php';
//$meta_keywords = socialnet_createmeta_keywords($myts->displayTarea($firstcontent));
if(isset($xoTheme) && is_object($xoTheme)) {
	//$xoTheme->addMeta( 'meta', 'keywords', $meta_keywords);
} else {	// Compatibility for old Xoops versions
	$xoopsTpl->assign('xoops_meta_keywords', $meta_keywords);
}

$meta_description = strip_tags($myts->displayTarea($firsttitle));
if(isset($xoTheme) && is_object($xoTheme)) {
	$xoTheme->addMeta( 'meta', 'description', $meta_description);
} else {	// Compatibility for old Xoops versions
	$xoopsTpl->assign('xoops_meta_description', $meta_description);
}

// create jump box
$xoopsTpl->assign(array('forum_jumpbox' => make_jumpboxex($forum), 'lang_forum_index' => sprintf(_MD_SOCIALNET_FORUM_FORUMINDEX,$xoopsConfig['sitename']), 'lang_from' => _MD_SOCIALNET_FORUM_FROM, 'lang_joined' => _MD_SOCIALNET_FORUM_JOINED, 'lang_posts' => _MD_SOCIALNET_FORUM_POSTS, 'lang_poster' => _MD_SOCIALNET_FORUM_POSTER, 'lang_thread' => _MD_SOCIALNET_FORUM_THREAD, 'lang_edit' => _EDIT, 'lang_delete' => _DELETE, 'lang_reply' => _REPLY, 'lang_postedon' => _MD_SOCIALNET_FORUM_POSTEDON));

// Read in cookie of 'lastread' times
$topic_lastread = socialnet_get_topics_viewed();
// if cookie is not set for this topic, update view count and set cookie
if ( empty($topic_lastread[$topic_id]) ) {
	$sql = 'UPDATE '.$xoopsDB->prefix('socialnet_forumtopics').' SET topic_views = topic_views + 1 WHERE topic_id ='. $topic_id;
	$xoopsDB->queryF($sql);
}
// Update cookie
socialnet_add_topics_viewed($topic_lastread, $topic_id, time(), $bbCookie['path'], $bbCookie['domain'], $bbCookie['secure']);

/**
* Adding to the module js and css of the lightbox and new ones
*/
$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/socialnet.css');
$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/jquery.tabs.css');
$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/leftmenu.css');
// what browser they use if IE then add corrective script.
if(ereg('msie', strtolower($_SERVER['HTTP_USER_AGENT']))) {$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/jquery.tabs-ie.css');}

$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/jquery.lightbox-0.5.css');
$xoTheme->addScript(XOOPS_URL.'/modules/socialnet/js/jquery.js');
$xoTheme->addScript(XOOPS_URL.'/modules/socialnet/js/jquery.lightbox-0.5.js');
//$xoTheme->addScript(XOOPS_URL.'/modules/socialnet/js/socialnet.js');

// SocialNetFaceStyle starts here
$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/barfacestyle.css');
$xoTheme->addStylesheet(XOOPS_URL.'/modules/socialnet/css/barface.stylesheet.css');
$xoTheme->addScript(XOOPS_URL.'/modules/socialnet/js/barface.readyfunction.js');
$xoTheme->addScript(XOOPS_URL.'/modules/socialnet/js/jquery-1.3.2.min.js');
$xoTheme->addScript(XOOPS_URL.'/modules/socialnet/js/jixedbar-0.0.3-dev.js');
// SocialNetFaceStyle End here

//navbar
$xoopsTpl->assign('module_name',$xoopsModule->getVar('name'));
$xoopsTpl->assign('lang_mysection',_MD_SOCIALNET_FORUM_FORUM);
$xoopsTpl->assign('section_name',_MD_SOCIALNET_FORUM_FORUM);
$xoopsTpl->assign('lang_home',_MD_SOCIALNET_HOME);
$xoopsTpl->assign('lang_photos',_MD_SOCIALNET_PHOTOS);
$xoopsTpl->assign('lang_friends',_MD_SOCIALNET_FRIENDS);
$xoopsTpl->assign('lang_audio',_MD_SOCIALNET_AUDIOS);
$xoopsTpl->assign('lang_videos',_MD_SOCIALNET_VIDEOS);
$xoopsTpl->assign('lang_scrapbook',_MD_SOCIALNET_SCRAPBOOK);
$xoopsTpl->assign('lang_profile',_MD_SOCIALNET_PROFILE);
$xoopsTpl->assign('lang_groups',_MD_SOCIALNET_GROUPS);
$xoopsTpl->assign('lang_configs',_MD_SOCIALNET_CONFIGSTITLE);
$xoopsTpl->assign('lang_search', _MD_SOCIALNET_SEARCH);
$xoopsTpl->assign('lang_membership', _MD_SOCIALNET_MEMBERSHIP);
$xoopsTpl->assign('lang_pagelist', _MD_SOCIALNET_PAGELIST);
$xoopsTpl->assign('lang_publish', _MD_SOCIALNET_PUBLISH);
$xoopsTpl->assign('lang_your_page', _MD_SOCIALNET_YOUR_PAGE);
$xoopsTpl->assign('lang_contactus', _MD_SOCIALNET_CONTACTUS);
$xoopsTpl->assign('lang_articles', _MD_SOCIAL_ARTICLES);
$xoopsTpl->assign('lang_popchatmenu', _MD_SOCIALNET_POPCHATMENU);
$xoopsTpl->assign('lang_forum', _MD_SOCIALNET_FORUM_FORUM);

include("../../footer.php");


?>
