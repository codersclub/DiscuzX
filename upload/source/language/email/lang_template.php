<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: lang_email.php 35030 2014-10-23 07:43:23Z laoguozhang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


$lang = array
(
	'hello' => '您好',
	'moderate_member_invalidate' => '否决',
	'moderate_member_delete' => '删除',
	'moderate_member_validate' => '通过',

	'comma' => '，',
	'show_sender' => '这封信是由 {$var[\'bbname\']} 发送的。',
	'show_reason' => '您收到这封邮件，是由于',
	'have_not_visit' => '如果您并没有访问过 {$var[\'bbname\']}，',
	'have_not_do_this' => '或没有进行上述操作，',
	'not_interested' => '如果您对此不感兴趣，',
	'ignore_email' => '请忽略这封邮件。',
	'no_more_action' => '您不需要退订或进行其他进一步的操作。',
	'important' => '重要！',
	'if_not_link' => '如果上面不是链接形式，请将该地址手工粘贴到浏览器地址栏再访问',
	'msg_start' => '信件原文开始',
	'msg_end' => '信件原文结束',
	'not_responsible' => '网站管理团队不会对这类邮件负责。',
	'show_ip' => '本请求提交者的 IP 为 {$var[\'clientip\']}',
	'welcome_visit' => '欢迎您访问 {$_G[\'setting\'][\'bbname\']}',
	'thanks_for_visit' => '感谢您的访问，祝您使用愉快！',
	'sincerely' => '此致',
	'admin_team' => '{$var[\'bbname\']} 管理团队',


	'get_passwd_subject' => '取回密码说明',
	'get_passwd_reason' => '这个邮箱地址在 {$var[\'bbname\']} 被登记为用户邮箱，且该用户请求使用 Email 密码重置功能所致。',
	'get_passwd_if_not' => '如果您没有提交密码重置的请求或不是 {$var[\'bbname\']} 的注册用户，请立即忽略并删除这封邮件。只有在您确认需要重置密码的情况下，才需要继续阅读下面的内容。',
	'get_passwd_explain' => '密码重置说明',
	'get_passwd_click_link' => '您只需在提交请求后的三天内，通过点击下面的链接重置您的密码：',
	'get_passwd_new_pwd' => '在上面的链接所打开的页面中输入新的密码后提交，您即可使用新的密码登录网站了。您可以在用户控制面板中随时修改您的密码。',

	'password_reset_subject' => '密码变更提示',
	'password_reset_reason' => '在 {$var[\'bbname\']} 被登记为用户邮箱，且该用户操作重置或者变更了密码所致。',
	'password_reset_if_not' => '如果您不是 {$var[\'bbname\']} 的注册用户，请立即忽略并删除这封邮件。只有在您是 {$var[\'bbname\']} 的注册用户的情况下，才需要继续阅读下面的内容。',
	'password_reset_explain' => '您在 {$var[\'bbname\']} 的用户账户 {$var[\'username\']} 在 {$var[\'datetime\']} 进行了密码变更或重置。',
	'password_reset_if_not_user_op' => '如果您没有操作密码变更或者重置，请您立即登录 {$var[\'bbname\']} 检查账户情况，并进行变更密码操作。',
	'password_reset_if_not_user_op_help' => '在处理问题时如果您有任何问题或需要帮助（如冻结账户），请联系 {$var[\'bbname\']} 管理团队获取更多帮助与支持。',

	'email_verify_subject' => 'Email 地址验证',
	'email_verify_reason' => '在 {$var[\'bbname\']} 进行了新用户注册，或用户修改 Email 使用了这个邮箱地址。',
	'email_verify_explain' => '帐号激活说明',
	'email_verify_explain2' => '如果您是 {$var[\'bbname\']} 的新用户，或在修改您的注册 Email 时使用了本地址，我们需要对您的地址有效性进行验证以避免垃圾邮件或地址被滥用。',
	'email_verify_click_link' => '您只需点击下面的链接即可激活您的帐号：',

	'email_reset_subject' => 'Email 地址变更提示',
	'email_reset_reason' => '在 {$var[\'bbname\']} 被登记为用户邮箱，且该用户操作 Email 地址变更所致。',
	'email_reset_if_not' => '如果您不是 {$var[\'bbname\']} 的注册用户，请立即忽略并删除这封邮件。只有在您是 {$var[\'bbname\']} 的注册用户的情况下，才需要继续阅读下面的内容。',
	'email_reset_explain' => '您在 {$var[\'bbname\']} 的用户账户 {$var[\'username\']} 在 {$var[\'datetime\']} 进行了 Email 地址变更。',
	'email_reset_new_email' => '新的 Email 地址为：{$var[\'email\']} ，验证邮件发送时间为：{$var[\'request_datetime\']}',
	'email_reset_if_not_user_op' => '如果您没有操作 Email 地址变更，请您立即登录 {$var[\'bbname\']} 检查账户情况，并进行变更密码和 Email 地址变更操作。',
	'email_reset_if_not_user_op_help' => '在处理问题时如果您有任何问题或需要帮助（如冻结账户），请联系 {$var[\'bbname\']} 管理团队获取更多帮助与支持。',

	'secmobile_reset_subject' => '安全手机号变更提示',
	'secmobile_reset_reason' => '在 {$var[\'bbname\']} 被登记为用户邮箱，且该用户操作变更了安全手机号所致。',
	'secmobile_reset_if_not' => '如果您不是 {$var[\'bbname\']} 的注册用户，请立即忽略并删除这封邮件。只有在您是 {$var[\'bbname\']} 的注册用户的情况下，才需要继续阅读下面的内容。',
	'secmobile_reset_explain' => '您在 {$var[\'bbname\']} 的用户账户 {$var[\'username\']} 在 {$var[\'datetime\']} 进行了安全手机号变更。',
	'secmobile_reset_new_secmobile' => '新的安全手机号为：{$var[\'secmobile\']}',
	'secmobile_reset_if_not_user_op' => '如果您没有操作安全手机号变更，请您立即登录 {$var[\'bbname\']} 检查账户情况，并进行变更密码和安全手机号变更操作。',
	'secmobile_reset_if_not_user_op_help' => '在处理问题时如果您有任何问题或需要帮助（如冻结账户），请联系 {$var[\'bbname\']} 管理团队获取更多帮助与支持。',

	'email_register_subject' =>	'论坛注册地址',
	'email_register_reason' => '在 {$var[\'bbname\']} 获取了新用户注册地址使用了这个邮箱地址。',
	'email_register_explain' => '新用户注册说明',
	'email_register_click_link' => '您只需点击下面的链接即可进行用户注册，以下链接有效期为3天。过期可以重新请求发送一封新的邮件验证：',

	'add_member_subject' => '您被添加成为会员',
	'add_member_intro' => '我是 {$var[\'adminusername\']} ，{$var[\'bbname\']} 的管理者之一。',
	'add_member_reason' => '您刚刚被添加成为 {$var[\'bbname\']} 的会员，当前 Email 即是我们为您注册的邮箱地址。',
	'add_member_no_interest' => '如果您对 {$var[\'bbname\']} 不感兴趣或无意成为会员，',
	'add_member_info' => '帐号信息',
	'add_member_bbname' => '网站名称：',
	'add_member_siteurl' => '网站地址：',
	'add_member_newusername' => '用户名：',
	'add_member_newpassword' => '密码：',
	'add_member_can_login' => '从现在起您可以使用您的帐号登录 {$var[\'bbname\']}，祝您使用愉快！',

	'birthday_subject' => '祝您生日快乐',
	'birthday_reason' => '这个邮箱地址在 {$var[\'bbname\']} 被登记为用户邮箱，<br />
并且按照您填写的信息，今天是您的生日。很高兴能在此时为您献上一份生日祝福，<br />
我谨代表{$var[\'bbname\']}管理团队，衷心祝福您生日快乐。',
	'birthday_if_not' => '如果您并非 {$var[\'bbname\']} 的会员，或今天并非您的生日，可能是有人误用了您的邮件地址，<br />
或错误的填写了生日信息。本邮件不会多次重复发送，',

	'email_to_friend_subject' => '{$_G[\'member\'][\'username\']} 推荐给您: {$thread[\'subject\']}',
	'email_to_friend_sender' => '这封信是由 {$_G[\'setting\'][\'bbname\']} 的 {$_G[\'member\'][\'username\']} 发送的。',
	'email_to_friend_reason' => '在 {$_G[\'member\'][\'username\']} 通过 {$_G[\'setting\'][\'bbname\']} 的“推荐给朋友”功能推荐了如下的内容给您。',
	'email_to_friend_not_official' => '请注意这封信仅仅是由用户使用 “推荐给朋友”发送的，不是网站官方邮件，',

	'email_to_invite_subject' => '您的朋友 {$_G[\'member\'][\'username\']} 发送 {$_G[\'setting\'][\'bbname\']} 网站注册邀请码给您',
	'email_to_invite_reason' => ' {$_G[\'member\'][\'username\']} 通过 {$var[\'bbname\']} 的“发送邀请码给朋友” 功能推荐了如下的内容给您。',
	'email_to_invite_not_official' => '请注意这封信仅仅是由用户使用 “发送邀请码给朋友”发送的，不是网站官方邮件，',

	'invitemail_subject' => '{username}邀请您加入{sitename}，并成为好友',
	'invitemail_from' => 'Hi，我是{$var[\'username\']}，邀请您也加入{$var[\'sitename\']}并成为我的好友',
	'invitemail_reason' => '请加入到我的好友中，您就可以了解我的近况，与我一起交流，随时与我保持联系。',
	'invitemail_start' => '邀请附言：',
	'invitemail_accept_invite' => '请您点击以下链接，接受好友邀请：',
	'invitemail_viewpage' => '如果您拥有{$var[\'sitename\']}上面的账号，请点击以下链接查看我的个人主页：',

	'moderate_member_subject' => '用户审核结果通知',
	'moderate_member_reason' => '这个邮箱地址在 {$var[\'bbname\']} 被新用户注册时所使用，且管理员设置了对新用户需要进行人工审核，本邮件将通知您提交申请的审核结果。',
	'moderate_member_info' => '注册信息与审核结果',
	'moderate_member_username' => '用户名：',
	'moderate_member_regdate' => '注册时间：',
	'moderate_member_submitdate' => '提交时间：',
	'moderate_member_submittimes' => '提交次数：',
	'moderate_member_msg' => '注册原因：',
	'moderate_member_modresult' => '审核结果：',
	'moderate_member_moddate' => '审核时间：',
	'moderate_member_adminusername' => '审核管理员：',
	'moderate_member_remark' => '管理员留言：',
	'moderate_member_explain' => '审核结果说明',
	'moderate_member_explain1' => '通过: 您的注册已通过审核，您已成为 {$var[\'bbname\']} 的正式用户。',
	'moderate_member_explain2' => '否决: 您的注册信息不完整，或未满足我们对新用户的某些要求，您可以根据管理员留言，<a href="home.php?mod=spacecp&ac=profile" target="_blank">完善您的注册信息</a>，然后再次提交。',
	'moderate_member_explain3' => '删除：您的注册由于与我们的要求偏差较大，或本站的新注册人数已超过预期，申请已被否决。您的帐号已从数据库中删除，将无法再使用其登录或提交再次审核，请您谅解。',

	'adv_expiration_subject' =>	'您站点的广告将于 {day} 天后到期，请及时处理',
	'adv_expiration_msg' =>	'您站点的以下广告将于 {$var[\'day\']} 天后到期，请及时处理：',
);

?>