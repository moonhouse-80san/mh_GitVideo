var selected_node = null;

function escapeAttr(str) {
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

/* ── 본문에는 주소를 base64(b64: 접두사)로 저장 — 에드온이 URL을 찾아 서버로 내려받아 바꾸는 것을 방지 ── */
function encodeSrc(src) {
	return 'b64:' + btoa(unescape(encodeURIComponent(src))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function decodeSrc(str) {
	if (str.indexOf('b64:') !== 0) return str; // 이전 버전에서 저장된 일반 주소
	try {
		var b64 = str.substring(4).replace(/-/g, '+').replace(/_/g, '/');
		while (b64.length % 4) b64 += '=';
		return decodeURIComponent(escape(atob(b64)));
	} catch (e) {
		return '';
	}
}

/* ── GitHub 파일 화면 주소(blob/raw)를 영상 재생이 가능한 raw.githubusercontent.com 주소로 변환 ── */
function convertGithubUrl(src) {
	return src.replace(/^https?:\/\/(?:www\.)?github\.com\/([^\/?#]+)\/([^\/?#]+)\/(?:blob|raw)\/([^?#]+)(?:[?#].*)?$/i,
		'https://raw.githubusercontent.com/$1/$2/$3');
}

/* ── 에디터에서 기존 mh_GitVideo를 선택해 열었을 때(수정 모드) 값 복원 ── */
function getMhVideo() {
	// 부모창이 있는지 체크
	if (typeof(opener) == "undefined") return;

	// 부모 위지윅 에디터에서 선택된 영역이 있으면 처리
	var $node = jQuery(opener.editorPrevNode);
	if (!$node.is('img') || $node.attr('editor_component') != 'mh_GitVideo') return;

	selected_node = $node[0];

	get_by_id('video_src').value = decodeSrc($node.attr('mh_src') || '');
	get_by_id('video_width').value = $node.attr('mh_width') || '';

	var saved_align = $node.attr('mh_align') || 'center';
	var align_radios = document.getElementsByName('video_align');
	for (var i = 0; i < align_radios.length; i++) {
		align_radios[i].checked = (align_radios[i].value == saved_align);
	}

	var saved_autoplay = ($node.attr('mh_autoplay') == 'Y') ? 'Y' : 'N';
	// 1.0 버전으로 삽입된 글(mh_muted 없음)은 자동재생이면 음소거였으므로 동일하게 복원
	var saved_muted = $node.attr('mh_muted') || saved_autoplay;
	var muted_radios = document.getElementsByName('video_muted');
	for (var k = 0; k < muted_radios.length; k++) {
		muted_radios[k].checked = (muted_radios[k].value == saved_muted);
	}
	var autoplay_radios = document.getElementsByName('video_autoplay');
	for (var j = 0; j < autoplay_radios.length; j++) {
		autoplay_radios[j].checked = (autoplay_radios[j].value == saved_autoplay);
	}
}

/* ── 에디터에 삽입(또는 선택된 노드 수정) ── */
function insertMhVideo() {
	if (typeof(opener) == "undefined") return;

	var $fo = jQuery('#fo');
	var src = convertGithubUrl(get_by_id('video_src').value.trim());
	get_by_id('video_src').value = src;
	var width = get_by_id('video_width').value.trim();

	if (!src) {
		alert($fo.data('msgSrcRequired'));
		get_by_id('video_src').focus();
		return;
	}
	// javascript:, data: 등 http/https 이외의 스킴은 허용하지 않음 (스킴이 없는 사이트 내 경로는 허용)
	if (/^[a-z][a-z0-9+.\-]*:/i.test(src) && !/^https?:/i.test(src)) {
		alert($fo.data('msgSrcInvalid'));
		get_by_id('video_src').focus();
		return;
	}
	if (width && !/^[1-9][0-9]*$/.test(width)) {
		alert($fo.data('msgWidthInvalid'));
		get_by_id('video_width').focus();
		return;
	}

	var align = document.querySelector('input[name="video_align"]:checked').value;
	var autoplay = document.querySelector('input[name="video_autoplay"]:checked').value;
	var muted = document.querySelector('input[name="video_muted"]:checked').value;

	// 에디터 작성화면에는 영상이 보이지 않으므로, 점선 박스(16:9)로 위치/넓이/정렬만 미리 보여줌
	// (실제 저장되는 값은 mh_* 속성이며, 글을 볼 때 transHTML()이 video 태그로 변환)
	var preview_width = width ? parseInt(width, 10) : 640;
	var preview_height = Math.round(preview_width * 9 / 16);
	var preview_style = "display:block;width:" + (width ? width + "px" : "100%") + ";max-width:100%;height:" + preview_height + "px;"
		+ "border:2px dotted #4371B9;background:#222 url(./modules/editor/components/mh_GitVideo/tpl/placeholder.png) no-repeat center;"
		+ "margin-left:" + (align == "left" ? "0" : "auto") + ";margin-right:" + (align == "right" ? "0" : "auto") + ";";

	if (selected_node) {
		selected_node.setAttribute("mh_src", encodeSrc(src));
		selected_node.setAttribute("mh_width", width);
		selected_node.setAttribute("mh_align", align);
		selected_node.setAttribute("mh_autoplay", autoplay);
		selected_node.setAttribute("mh_muted", muted);
		selected_node.setAttribute("style", preview_style);
	} else {
		var text = "<img src=\"../../../../common/img/blank.gif\" editor_component=\"mh_GitVideo\""
			+ " mh_src=\"" + encodeSrc(src) + "\" mh_width=\"" + width + "\""
			+ " mh_align=\"" + align + "\" mh_autoplay=\"" + autoplay + "\" mh_muted=\"" + muted + "\""
			+ " style=\"" + preview_style + "\" />";
		opener.editorFocus(opener.editorPrevSrl);
		var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl);
		opener.editorReplaceHTML(iframe_obj, text);
	}

	opener.editorFocus(opener.editorPrevSrl);

	window.close();
}

jQuery(function($) {
	getMhVideo();

	// 주소를 붙여넣고 입력창을 벗어나면 GitHub 주소를 바로 raw 주소로 바꿔서 보여줌
	$('#video_src').on('change', function() {
		this.value = convertGithubUrl(this.value.trim());
	});
});
