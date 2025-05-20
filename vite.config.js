import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";

export default defineConfig({
	root: "src/resources/src",
	base: "/dist/", // public path for production
	plugins: [vue()],
	build: {
		target: "esnext", //browsers can handle the latest ES features
		outDir: "../dist",
		emptyOutDir: true,
		rollupOptions: {
			input: [
				resolve(__dirname, "src/resources/src/js/main.js"),
				resolve(__dirname, "src/resources/src/js/index.js"),
				resolve(__dirname, "src/resources/src/js/dashboard.js"),
				resolve(__dirname, "src/resources/src/js/highlight.js"),
				resolve(__dirname, "src/resources/src/css/guides.css"),
			],
			output: {
				entryFileNames: "js/[name].js",
				assetFileNames: "css/[name].[ext]",
			},
		},
	},
	server: {
		origin: "http://localhost:3000",
		hmr: {
			protocol: "ws",
			host: "localhost",
		},
	},
});
