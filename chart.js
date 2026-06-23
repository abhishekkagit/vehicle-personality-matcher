const ctx = document.getElementById('radarChart');

new Chart(ctx,{
type:'radar',
data:{
labels:[
'Performance',
'Comfort',
'Efficiency',
'Reliability',
'Practicality'
],
datasets:[
{
label:'Vehicle 1',
data:[82,75,70,80,78],
borderColor:'#3b82f6',
backgroundColor:'rgba(59,130,246,0.2)'
},
{
label:'Vehicle 2',
data:[88,72,65,76,70],
borderColor:'#ef4444',
backgroundColor:'rgba(239,68,68,0.2)'
}
]
}
});