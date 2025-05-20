import ApexCharts from "apexcharts";
import "../css/dashboard.css";

async function initCharts() {
	// Send the data backend record
	const response = await fetch("/actions/forty-cookieconsent/cookies/get-consent", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
			"X-CSRF-Token": Craft.csrfTokenValue,
		},
	});

	const body = await response.json();

	if (response.ok) {
		var options = {
			series: body.data,
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

		var chart = new ApexCharts(document.querySelector("#chart"), options);

		chart.render();
	}
}

initCharts();
