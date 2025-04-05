function showTab(sectionId) {
    document.querySelectorAll('.tab-content').forEach(section => {
        section.classList.remove('active');
    });
    document.querySelectorAll('.tab-link').forEach(link => {
        link.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
    document.querySelector(`a[href="#${sectionId.split('-')[0]}"]`).classList.add('active');
}
const row1=document.getElementById('row1').textContent;
const row2=document.getElementById('row2').textContent;
const row3=document.getElementById('row3').textContent;
const row4=document.getElementById('row4').textContent;
const row5=document.getElementById('row5').textContent;
const average=document.getElementById('average').textContent;
const rank=document.getElementById('rank').textContent;
const status=document.getElementById('status').textContent;
if(row1=='-' || row2=='-'|| row3=='-'|| row4=='-'|| row5=='-' )
{
    average=rank=status='-';

}