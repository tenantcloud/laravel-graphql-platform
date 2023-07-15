<style>
	body {
		margin: 0;
	}
</style>

<div style="width: 100%; height: 100%;" id="embedded-sandbox"></div>

<script src="https://embeddable-sandbox.cdn.apollographql.com/_latest/embeddable-sandbox.umd.production.min.js"></script>
<script>
	new window.EmbeddedSandbox({
		target: '#embedded-sandbox',
		initialEndpoint: @js($endpoint),
	});
</script>
