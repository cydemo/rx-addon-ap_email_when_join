<?php
if ( !defined('RX_VERSION') ) return;
if ( $called_position !== 'before_module_init' ) return;
if ( !$addon_info->to_admin && !$addon_info->to_member ) return;

getController('module')->addTriggerFunction('member.insertMember', 'after', function($obj) use($addon_info)
{
	// 매크로 변수 생성
	$site_title = Context::getSiteTitle();
	$macros = array(
		'{SITE_NAME}' => htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8', false),
		'{USER_ID}' => htmlspecialchars($obj->user_id, ENT_QUOTES, 'UTF-8', false),
		'{USER_NAME}' => htmlspecialchars($obj->user_name, ENT_QUOTES, 'UTF-8', false),
		'{NICK_NAME}' => htmlspecialchars($obj->nick_name, ENT_QUOTES, 'UTF-8', false),
		'{EMAIL}' => htmlspecialchars($obj->email_address, ENT_QUOTES, 'UTF-8', false),
		'{REGDATE}' => date('Y년 n월 j일'),
		'{PASSWORD}' => htmlspecialchars($obj->password, ENT_QUOTES, 'UTF-8', false)
	);

	// 관리자 알림
	if ( $addon_info->to_admin )
	{
		// 메일 제목
		$ttl_to_admin = ( $addon_info->ttl_to_admin ) ? htmlspecialchars_decode(str_replace(array_keys($macros), array_values($macros), $addon_info->ttl_to_admin)) : '신규 회원 가입 알림';
		// 메일 내용
		if ( $addon_info->msg_to_admin )
		{
			$msg_to_admin = str_replace(array_keys($macros), array_values($macros), $addon_info->msg_to_admin);
		}
		else
		{
			$msg_to_admin = '<p style="margin-bottom: 2em">새로운 회원이 가입했습니다.</p>';
			$msg_to_admin .= '<ul style="list-style-type: none; margin: 3em 0; padding: 0;">';
				$msg_to_admin .= '<li><strong>아이디 : </strong>'. $obj->user_id .'</li>';
				$msg_to_admin .= '<li><strong>닉네임 : </strong>'. $obj->nick_name .'</li>';
				$msg_to_admin .= '<li><strong>이메일주소 : </strong>'. $obj->email_address .'</li>';
			$msg_to_admin .= '</ul>';
		}

		// 발신 사항 처리
		$oMail = new Rhymix\Framework\Mail();
		$oMail->setTitle($ttl_to_admin);
		$oMail->setContent($msg_to_admin);
		$default_domain = getModel('module')->getDefaultDomainInfo()->domain;
		$oMail->setFrom('noreply@' . $default_domain, $obj->nick_name);

		// 수신 사항 처리
		if ( $addon_info->to_admin === 'W' )
		{
			$member_config = getModel('member')->getMemberConfig();
			$oMail->addTo($member_config->webmaster_email, $member_config->webmaster_name);
			$oMail->send();
		}
		else if ( $addon_info->to_admin === 'A' )
		{
			$admins = executeQueryArray('member.getAdmins')->data;
			foreach( $admins as $val )
			{
				$admin_info = getModel('member')->getMemberInfoByMemberSrl($val->member_srl);
				$oMail->addTo($admin_info->email_address, $admin_info->nick_name);
				$oMail->send();
			}
		}
	}

	// 가입자 알림
	if ( $addon_info->to_member === 'A' || ($addon_info->to_member === 'M' && $obj->allow_mailing !== 'N') )
	{
		// 메일 제목
		$ttl_to_member = ( $addon_info->ttl_to_member ) ? htmlspecialchars_decode(str_replace(array_keys($macros), array_values($macros), $addon_info->ttl_to_member)) : $site_title . ' 회원 가입 환영';
		// 메일 내용
		if ( $addon_info->msg_to_member )
		{
			$msg_to_member = str_replace(array_keys($macros), array_values($macros), $addon_info->msg_to_member);
		}
		else
		{
			$msg_to_member = '<p>'. $obj->nick_name . '님! '.$site_title .'에 가입하신 것을 진심으로 환영합니다.</p>';
			$msg_to_member = '<p style="margin-bottom: 2em">'. $obj->nick_name . '님의 가입 정보는 다음과 같습니다.</p>';
			$msg_to_member .= '<ul style="list-style-type: none; margin: 3em 0; padding: 0;">';
				$msg_to_member .= '<li><strong>아이디 : </strong>'. $obj->user_id .'</li>';
				$msg_to_member .= '<li><strong>이름 : </strong>'. $obj->user_name .'</li>';
				$msg_to_member .= '<li><strong>이메일주소 : </strong>'. $obj->email_address .'</li>';
			$msg_to_member .= '</ul>';
		}

		// 발신 사항 처리
		$oMail = new Rhymix\Framework\Mail();
		$oMail->setTitle($ttl_to_member);
		$oMail->setContent($msg_to_member);
		$member_config = getModel('member')->getMemberConfig();
		$oMail->setFrom($member_config->webmaster_email, $member_config->webmaster_name);

		// 수신 사항 처리
		$oMail->addTo($obj->email_address, $obj->nick_name);
		$oMail->send();
	}

	return $obj;
});
?>