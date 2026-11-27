# Github 비디오 삽입 MH GitVideo 1.1

비디오 주소(src)를 직접 입력해 정렬·넓이·자동재생을 지정하고 본문에 삽입하는 에디터 컴포넌트입니다.

| 항목 | 내용 |
| --- | --- |
| 종류 | 에디터 컴포넌트 |
| 버전 | 1.1 (2026-10-11) |
| 설치 경로 | `/modules/editor/components/mh_GitVideo` |
| 자료실 | https://moonhouse.co.kr/xemy/620361 |
| 적용 예 | https://moonhouse.co.kr/comtip/589318 |
| 저작자 | 팔공산 (80san) |

## 주요 기능

- GitHub에 올린 동영상 파일 화면 주소(`github.com/.../blob/...`)를 입력하면 **raw 주소로 자동 변환**해 본문에서 바로 재생합니다.
- Google Drive 파일 주소는 `preview` 형식으로 바꿔 삽입합니다.
- YouTube 주소(watch, youtu.be, shorts, embed, live)는 `youtube-nocookie` 임베드로 출력합니다.
- 직접 재생: `mp4`, `m4v`, `webm`
- 옵션: 넓이(기본 640px / 100%), 정렬, 자동재생, 소리 켜기

## 요구 사항

- 라이믹스 2.x, PHP 8.0 이상 권장
- CKEditor 에디터

## 설치

1. 압축을 풀어 `mh_GitVideo` 폴더를 `/modules/editor/components/mh_GitVideo`에 업로드합니다.
2. 관리자 > 에디터 설정에서 **Github 비디오 삽입** 항목을 체크합니다.
3. 글쓰기 화면의 컴포넌트 버튼에서 비디오를 삽입합니다.

## 사용법

1. GitHub에 mp4/webm 파일을 올립니다.
2. 파일 화면의 주소를 복사합니다. (예: `https://github.com/내계정/저장소/blob/main/video.mp4`)
   - `raw.githubusercontent.com/...` 주소를 넣어도 됩니다.
3. 글쓰기 컴포넌트 팝업의 비디오 주소에 붙여 넣고 넓이·정렬·자동재생·소리를 고른 뒤 삽입합니다.

## 구성 파일

```
mh_GitVideo/
├─ info.xml
├─ mh_GitVideo.class.php     # 주소 판별·변환 (raw / Drive / YouTube)
├─ lang/
└─ tpl/
   ├─ popup.html / popup.js / popup.css   # 삽입 팝업
   ├─ display.html           # 일반 동영상
   ├─ display_drive.html     # Google Drive
   └─ display_youtube.html   # YouTube
```

## 주의 사항

- GitHub 저장소가 **비공개**면 raw 주소로도 재생되지 않습니다. 공개 저장소나 공개 파일만 사용하세요.
- 파일이 크면 GitHub에서 바로 재생되지 않거나 로딩이 길어질 수 있습니다. 브라우저가 받아서 재생하는 방식이므로 100MB 이하 정도를 권합니다.
- 자동재생을 켜도 브라우저 정책상 소리는 함께 재생되지 않습니다. 소리를 원하면 소리 옵션을 켜고 방문자가 재생 버튼을 눌러야 합니다.
- GitHub은 파일당 용량 제한이 있습니다. 큰 영상은 YouTube나 Drive에 올리고 그 주소를 넣는 편이 낫습니다.
