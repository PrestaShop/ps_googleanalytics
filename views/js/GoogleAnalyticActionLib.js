/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 **/

/* globals $, ga, jQuery */

var GoogleAnalyticEnhancedECommerce = {

	setCurrency: function(Currency) {
		ga('set', '&cu', Currency);
	},

	add: function(Product, Order, Impression) {
		var Products = {};
		var Orders = {};

		var ProductFieldObject = ['id', 'name', 'category', 'brand', 'variant', 'price', 'quantity', 'coupon', 'list', 'position', 'dimension1'];
		var OrderFieldObject = ['id', 'affiliation', 'revenue', 'tax', 'shipping', 'coupon', 'list', 'step', 'option'];

		if (Product != null) {
			if (Impression && Product.quantity !== undefined) {
				delete Product.quantity;
			}

			for (var productKey in Product) {
				for (var i = 0; i < ProductFieldObject.length; i++) {
					if (productKey.toLowerCase() == ProductFieldObject[i]) {
						if (Product[productKey] != null) {
							Products[productKey.toLowerCase()] = Product[productKey];
						}

					}
				}

			}
		}

		if (Order != null) {
			for (var orderKey in Order) {
				for (var j = 0; j < OrderFieldObject.length; j++) {
					if (orderKey.toLowerCase() == OrderFieldObject[j]) {
						Orders[orderKey.toLowerCase()] = Order[orderKey];
					}
				}
			}
		}

		if (Impression) {
			ga('ec:addImpression', Products);
		} else {
			ga('ec:addProduct', Products);
		}
	},

	addProductDetailView: function(Product) {
		this.add(Product);
		ga('ec:setAction', 'detail');
		ga('send', 'event', 'UX', 'detail', 'Product Detail View',{'nonInteraction': 1});
	},

	addToCart: function(Product) {
		this.add(Product);
		ga('ec:setAction', 'add');
		ga('send', 'event', 'UX', 'click', 'Add to Cart'); // Send data using an event.
	},

	removeFromCart: function(Product) {
		this.add(Product);
		ga('ec:setAction', 'remove');
		ga('send', 'event', 'UX', 'click', 'Remove From cart'); // Send data using an event.
	},

	addProductImpression: function(Product) {
		//ga('send', 'pageview');
	},

	/**
	id, type, affiliation, revenue, tax, shipping and coupon.
	**/
	refundByOrderId: function(Order) {
		/**
		 * Refund an entire transaction.
		 **/
		ga('ec:setAction', 'refund', {
			'id': Order.id // Transaction ID is only required field for full refund.
		});
		ga('send', 'event', 'Ecommerce', 'Refund', {'nonInteraction': 1});
	},

	refundByProduct: function(Order) {
		/**
		 * Refund a single product.
		 **/
		//this.add(Product);

		ga('ec:setAction', 'refund', {
			'id': Order.id, // Transaction ID is required for partial refund.
		});
		ga('send', 'event', 'Ecommerce', 'Refund', {'nonInteraction': 1});
	},

	addProductClick: function(Product) {
		var ClickPoint = jQuery('a[href$="' + Product.url + '"].quick-view');

		ClickPoint.on("click", function() {
			GoogleAnalyticEnhancedECommerce.add(Product);
			ga('ec:setAction', 'click', {
				list: Product.list
			});

			ga('send', 'event', 'Product Quick View', 'click', Product.list, {
				'hitCallback': function() {
					return !ga.loaded;
				}
			});
		});

	},

	addProductClickByHttpReferal: function(Product) {
		this.add(Product);
		ga('ec:setAction', 'click', {
			list: Product.list
		});

		ga('send', 'event', 'Product Click', 'click', Product.list, {
			'nonInteraction': 1,
			'hitCallback': function() {
				return !ga.loaded;
			}
		});

	},

	addTransaction: function(Order) {

		//this.add(Product);
		ga('ec:setAction', 'purchase', Order);
		ga('send', 'event','Transaction','purchase', {
			'hitCallback': function() {
				$.get(Order.url, {
					orderid: Order.id,
					customer: Order.customer
				});
			}
		});

	},

	addCheckout: function(Step) {
		ga('ec:setAction', 'checkout', {
			'step': Step
			//'option':'Visa'
		});
		//ga('send', 'pageview');
	}
};
