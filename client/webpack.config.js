var ExtractTextPlugin = require('extract-text-webpack-plugin');
var webpack = require('webpack');

module.exports = {
    entry: {
        app:  "./src/js/app",
        lib: [
            "axios",
            "react", 
            "react-dom",
            "lodash"
        ]
    },
    output: {
        path: "./dist",
        filename: "js/[name].bundle.js",
    },

    // Enable sourcemaps for debugging webpack's output.
    devtool: "source-map",

    resolve: {
        // Add '.ts' and '.tsx' as resolvable extensions.
        extensions: ["", ".webpack.js", ".web.js", ".ts", ".tsx", ".js"]
    },

    module: {
        loaders: [
            // All files with a '.ts' or '.tsx' extension will be handled by 'ts-loader'.
            {
                test: /\.tsx?$/, 
                loader: "ts-loader" 
            },
            { 
                test: /\.css$/, 
                loader: ExtractTextPlugin.extract("style-loader", "css-loader")
            },
            {
                test: /\.less$/,
                loader: ExtractTextPlugin.extract("style-loader", "css-loader!less-loader")
            }
        ],

        preLoaders: [
            // All output '.js' files will have any sourcemaps re-processed by 'source-map-loader'.
            { test: /\.js$/, loader: "source-map-loader" }
        ]
    },

    plugins: [
      new ExtractTextPlugin("css/bundle.css"),
      new webpack.optimize.CommonsChunkPlugin({
          name: "lib", // Name of this commons chunk, not related to entry.lib
          filename: "js/lib.bundle.js",
      })
    ]
};

