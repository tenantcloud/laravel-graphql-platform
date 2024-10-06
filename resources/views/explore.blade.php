<html>
	<head>
		<title>GraphQL Explorer</title>

		<style>
			body {
				margin: 0;
			}

			#embedded-sandbox {
				width: 100vw;
				height: 100vh;
				position: absolute;
				top: 0;
			}
		</style>

		<script src="https://embeddable-sandbox.cdn.apollographql.com/_latest/embeddable-sandbox.umd.production.min.js"></script>
	</head>
	<body>
		<div id="embedded-sandbox"></div>

		<script>
			new window.EmbeddedSandbox({
				target: '#embedded-sandbox',
				initialEndpoint: @js($endpoint),
				runTelemetry: false,
				initialState: {
					document: null,
					headers: {
						accept: 'application/graphql-response+json, application/json, */*',
						version: @js($latestVersion),
					},
				}
			});
		</script>
	</body>
</html>
