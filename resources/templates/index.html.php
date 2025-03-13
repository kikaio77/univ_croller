
<form name="frmPost" class="participantform" action="/categories/buildexcel" method="POST">
	<div class="participants cont">
		<h2 class=>대상 웹사이트 주소</h2>
		<div class="input-cont"><input type="text" name="target" id="target" value="https://www.univstore.com" readonly></div>
		<div class="flex-middle p-10">
            <button type='button' class='btn btn-primary p-10' id="categoryRead">엑셀 생성하기</button>              
        </div>
	</div>
</form>


<script>

$('#categoryRead').click(() => {

    alert('현재 서버 상황에 따라, 적게는 3분에서 크게는 5분정도 소요될 수 있습니다.');

        const form = document.querySelector("form[name='frmPost']");
        const formData = new FormData(form);

    //FormData를 URL 인코딩 문자열로 변환 (서버가 URL 인코딩된 데이터를 기대하는 경우)
        const bodyData = new URLSearchParams(formData).toString();
        
        fetch("/categories/buildexcel", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: bodyData
        })
        .then(response => response.blob()) // 응답을 blob으로 변환
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            let now = new Date();
            let tostring = now.getFullYear()+'_'+(now.getMonth()+1)+'_'+now.getDate();
            a.download = `${tostring} univ_landing.xlsx`;  //다운로드될 파일명
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            alert("엑셀 다운로드에 성공했습니다.");
        })
        .catch(error => console.error("엑셀 다운로드 실패:", error));

	// });
});

</script>