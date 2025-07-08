import ApexCharts from "apexcharts";
import "../css/dashboard.css";

async function initCharts() {
	const container = document.querySelector("#chart");
	const accepted = container.getAttribute("data-accepted");
	const rejected = container.getAttribute("data-rejected");

	var options = {
		series: [parseInt(accepted), parseInt(rejected)],
		fill: {
			colors: ["#031c86", "#000"],
		},
		chart: {
			type: "donut",
		},
		dataLabels: {
			enabled: false,
		},
		plotOptions: {
			pie: {
				donut: {
					labels: {
						show: true,
						total: {
							showAlways: true,
							show: true,
						},
					},
				},
				expandOnClick: false,
			},
		},
		labels: ["Accepted", "Rejected"],
		responsive: [
			{
				breakpoint: 480,
				options: {
					chart: {
						width: 200,
					},
					legend: {
						position: "bottom",
					},
				},
			},
		],
	};

	var chart = new ApexCharts(container, options);

	chart.render();
}

initCharts();
