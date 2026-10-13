<?php
/**
 * @class  mh_GitVideo
 * @author 팔공산 (80san@moonhouse.co.kr)
 * @brief  비디오 주소(src)를 직접 입력해 정렬/넓이/자동재생을 지정하고 삽입하는 에디터 컴포넌트
 */
class mh_GitVideo extends EditorHandler
{
	var $editor_sequence = 0;
	var $component_path = '';

	/**
	 * @brief 확장자별 source type (목록에 없는 확장자는 type 속성을 생략하고 브라우저가 판단하게 함)
	 */
	var $mime_types = array(
		'mp4' => 'video/mp4',
		'm4v' => 'video/mp4',
		'webm' => 'video/webm',
		'ogv' => 'video/ogg',
		'ogg' => 'video/ogg'
	);

	function __construct($editor_sequence, $component_path)
	{
		$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;
	}

	/**
	 * @brief 팝업창에 표시할 설정 화면
	 */
	function getPopupContent()
	{
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';

		Context::set("tpl_path", $tpl_path);

		$oTemplate = TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief 에디터에 삽입된 placeholder <img> 태그를 실제 video HTML로 변환
	 */
	function transHTML($xml_obj)
	{
		$video_info = new stdClass();

		// src: 에드온 등이 본문의 URL을 서버로 내려받아 바꾸는 것을 막기 위해 팝업에서 base64(b64: 접두사)로 저장함
		// 이전 버전에서 저장된 일반 주소도 그대로 지원하며, 이 경우 &amp; 같은 HTML 엔티티를 디코딩
		$src = (string)($xml_obj->attrs->mh_src ?? '');
		$src = (strpos($src, 'b64:') === 0) ? (string)base64_decode(strtr(substr($src, 4), '-_', '+/')) : html_entity_decode($src, ENT_QUOTES, 'UTF-8');
		$src = trim($src);

		// GitHub 파일 화면 주소(github.com/.../blob/...)는 영상 재생이 안 되므로 raw 주소로 자동 변환
		$src = preg_replace('#^https?://(?:www\.)?github\.com/([^/?\#]+)/([^/?\#]+)/(?:blob|raw)/([^?\#]+)(?:[?\#].*)?$#i', 'https://raw.githubusercontent.com/$1/$2/$3', $src);
		$scheme = parse_url($src, PHP_URL_SCHEME);

		// 주소가 없거나, 제어문자가 있거나, http/https 외의 스킴(javascript:, data: 등)이면 출력하지 않음
		if ($src === '' || preg_match('/[\x00-\x1F\x7F]/', $src)
			|| ($scheme !== null && !in_array(strtolower((string)$scheme), array('http', 'https'), true)))
		{
			return '';
		}

		// 구글 드라이브 공유 주소(file/d/ID/view, open?id=ID, uc?id=ID)는 /preview iframe 방식으로 출력 (src는 검증된 ID로 새로 만들어 안전)
		$is_drive = preg_match('#^https?://drive\.google\.com/(?:file/(?:u/\d+/)?d/|open\?(?:[^\#]*&)?id=|uc\?(?:[^\#]*&)?id=)([A-Za-z0-9_-]{10,})#i', $src, $drive_match);

		// 소리는 기본 재생, 음소거(mh_muted=Y)는 옵션. (1.0 버전으로 삽입된 기존 글은 mh_muted가 없으므로 자동재생이면 음소거로 유지)
		$autoplay = (($xml_obj->attrs->mh_autoplay ?? '') === 'Y');
		$muted = isset($xml_obj->attrs->mh_muted) ? ($xml_obj->attrs->mh_muted === 'Y') : $autoplay;

		// 유튜브 주소(watch?v=, youtu.be/, shorts/, embed/, live/)는 youtube-nocookie embed iframe으로 출력 (src는 검증된 ID 11자로 새로 만들어 안전)
		// 자동재생/음소거 옵션과 주소의 시작시간(t=90, t=1m30s, start=90)을 그대로 반영
		$is_youtube = preg_match('#^https?://(?:(?:www\.|m\.|music\.)?youtube(?:-nocookie)?\.com/(?:watch\?(?:[^\#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $src, $youtube_match);
		if ($is_youtube)
		{
			$query = array('rel' => 0, 'playsinline' => 1);
			if ($autoplay) $query['autoplay'] = 1;
			if ($muted) $query['mute'] = 1;
			if (preg_match('/[?&#](?:t|start)=(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s?)?(?=[&#]|$)/i', $src, $time_match))
			{
				$start = (int)($time_match[1] ?? 0) * 3600 + (int)($time_match[2] ?? 0) * 60 + (int)($time_match[3] ?? 0);
				if ($start > 0) $query['start'] = $start;
			}
			$youtube_src = 'https://www.youtube-nocookie.com/embed/'.$youtube_match[1].'?'.http_build_query($query);
		}

		if ($is_drive) $src = 'https://drive.google.com/file/d/'.$drive_match[1].'/preview';
		elseif ($is_youtube) $src = $youtube_src;
		$video_info->src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');

		$ext = strtolower(pathinfo((string)parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
		$video_info->type_attr = isset($this->mime_types[$ext]) ? ' type="'.$this->mime_types[$ext].'"' : '';

		// 넓이: 숫자(px)를 지정하면 그 크기로, 비우면 100%. 화면이 좁으면 max-width:100%로 자동 축소(세로는 비율 유지)
		$width = (int)($xml_obj->attrs->mh_width ?? 0);
		$video_info->width_style = ($width > 0) ? 'width:'.$width.'px;max-width:100%;' : 'width:100%;';

		$align = (string)($xml_obj->attrs->mh_align ?? '');
		$video_info->align = in_array($align, array('left', 'center', 'right'), true) ? $align : 'center';

		$video_info->autoplay_attr = ($autoplay ? ' autoplay playsinline' : '').($muted ? ' muted' : '');

		Context::set('video_info', $video_info);

		$tpl_path = $this->component_path.'tpl';
		$tpl_file = $is_drive ? 'display_drive.html' : ($is_youtube ? 'display_youtube.html' : 'display.html');

		$oTemplate = TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
/* End of file mh_GitVideo.class.php */
/* Location: ./modules/editor/components/mh_GitVideo/mh_GitVideo.class.php */
