<template>
	<img v-if="url" :src="url" :width="width" :height="height" alt="">
</template>

<script>
import axios from 'axios'

export default {
	name: 'image-preview',
	props: {
  	documentId: {
  		default: ''
  	}
  },
  data() {
  	return {
  		url: '',
  		width: 100,
  		height: 100
  	}
  },
  methods: {
  	fetch() {
  		if( !this.documentId ) { return null }

			axios.get('/actions/imageshop/image/show', {
				params: {
					documentId: this.documentId
				}
			})
				.then(response => {
					console.log(response)
/*					if( response ) {
						const responseData = response.data.CreatePermaLinkFromDocumentIdResponse
						this.url = responseData.CreatePermaLinkFromDocumentIdResult
						this.width = responseData.resultwidth
						this.height = responseData.resultheight
					}*/
				})
				.catch(error => {
					console.log(error)
				})

  		console.log('fetch me some image')
  	}
  },
  watch: {
  	documentId: {
  		handler: 'fetch',
  		immediate: true
  	}
  }
}
</script>
<style lang="scss" scoped>

</style>
