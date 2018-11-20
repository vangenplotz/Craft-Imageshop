<template>
	<div>
		<input v-model="selectedValue" :name="namespacedId" type="hidden">

		<image-preview :document-id="selectedValue"></image-preview>

		<button class="btn add icon dashed" @click.stop.prevent="showModal">{{ buttonText }}</button>
		<div v-show="false">
			<div class="modal elementselectormodal" ref="modal">
				<div class="body has-sidebar">
					<div class="content has-sidebar">
						<div class="sidebar"></div>
						<div class="main">
							<div class="toolbar">
								<div class="flex">
									<div class="flex-grow texticon search icon clearable">
										<input v-model="query" class="text fullwidth" type="text" autocomplete="off" placeholder="Søk" ref="queryInput">
										<div v-show="query" class="clear" v-bind:title="$t('REMOVE')" @click="clearQuery"></div>
									</div>
									<div v-if="query && isFetchingResults" class="spinner"></div>
								</div>
							</div>
							<div class="elements" v-infinite-scroll="fetcMore">
								<ul v-if="results && results.length" class="thumbsview">
									<li v-for="result in results" v-if="result" :class="{'sel': result.DocumentID === tempValue}" :key="result.DocumentID" tabindex="0" @click.stop.prevent="tempValue = result.DocumentID">
										<div class="element large hasthumb">
											<div class="elementthumb">
												<img v-bind:src="result.ListThumbUrl">
											</div>
											<div class="label">{{ result.Name }}</div>
										</div>
									</li>
								</ul>
								<div v-if="isFetchingResults" class="spinner center"></div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="footer">
					<div class="spinner hidden"></div>
					<div class="buttons right">
						<div class="btn" tabindex="0" @click.stop.prevent="hideModal">Avbryt</div>
						<button :class="{'btn submit': true, 'disabled': !tempValue}" :disabled="!tempValue" @click="select">Velg</button>
					</div>
					<div class="buttons left secondary-buttons">
						<input type="file" multiple="multiple" name="assets-upload" style="display: none;">
						<div class="btn submit" data-icon="upload" style="position: relative; overflow: hidden;" role="button">Last opp filer</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import { debounce } from "debounce"

import ImagePreview from './components/ImagePreview.vue'

export default {
  name: 'imageshop',
  props: {
  	buttonText: {
  		default: 'Add image'
  	},
  	namespacedId: {
  		required: true
  	},
  	initValue: {
  		default: ''
  	}
  },
  data() {
  	return {
  		isFetchingResults: false,
  		modal: null,
  		page: 0,
  		pagesize: 50,
  		query: '',
  		resultCount: 0,
  		results: [],
  		selectedValue: '',
  		tempValue: '',
  	}
  },
  methods: {
  	clearQuery() {
  		this.query = ''
  		this.$refs.queryInput.focus()
  	},
  	createModal() {
  		this.modal = new Garnish.Modal(this.$refs.modal)
  	},
  	fetch() {
  		if( !this.modal ) { return }

  		const results = this.fetchResults()
  		const categories = this.fetchCategories()

  		return Promise.all([results, categories])

  	},
  	fetchCategories() {
  		return new Promise((resolve, reject) => {
  			resolve(true)
  		})
  	},
  	fetcMore() {
  		if( !this.modal ) { return }
  		this.page++
  		this.fetchResults(true)
  	},
  	fetchResults: debounce(function(append = false) {
  		return new Promise((resolve, reject) => {
  			this.isFetchingResults = true
  			axios.get('/actions/imageshop/search', {
  				params: {
  					page: this.page,
  					pagesize: this.pagesize,
  					query: this.query
  				}
  			})
				.then(response => {
		  			if( response && response.data ) {
						const searcResult = response.data.SearchResponse.SearchResult
						const results = searcResult.DocumentList.V4Document

						if( append ) {
							// Remove all results already in array when appending
							const newResults = results && Array.isArray(results) && results.filter( result => this.results.indexOf( item => item.DocumentID === result.DocumentID ) === -1 )

							this.results = this.results.concat(newResults)
						} else {
							this.results = results
						}

						this.resultCount = searcResult.NumberOfDocuments
					}
					resolve(response)
				})
				.catch(error => {
					reject(error)
				})
				.then(() => {
					this.isFetchingResults = false
				})
  		})
  	}, 300, true),
  	hideModal() {
  		this.modal && this.modal.hide()
  	},
  	select() {
  		this.selectedValue = this.tempValue
  		this.hideModal()
  	},
  	showModal() {
  		if( this.modal ) {
  			this.modal.show()
  		} else {
  			this.createModal()
  		}
  	}
  },
  mounted() {
  	this.selectedValue = this.initValue
  	this.fetch()
  },
  watch: {
  	modal: 'fetch',
  	query() {
  		this.page = 0
  		this.fetchResults()
  	}
  },
  components: {
  	ImagePreview
  }
}
</script>

<style lang="scss" scoped>
	button {
		font-size: 1em;
	}

	.center {
		display: block;
		margin: 0 auto;
	}
</style>
