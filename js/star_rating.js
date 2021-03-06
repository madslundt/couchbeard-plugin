/*!
 * @copyright &copy; Kartik Visweswaran, Krajee.com, 2014
 * @version 2.0.0
 *
 * A simple yet powerful JQuery star rating plugin that allows rendering
 * fractional star ratings and supports Right to Left (RTL) input.
 *
 * For more JQuery plugins visit http://plugins.krajee.com
 * For more Yii related demos visit http://demos.krajee.com
 */
(function(e) {
    var t = 0;
    var n = 5;
    var r = .5;
    var i = function() {
        var e = document.createElement("input");
        e.setAttribute("type", "range");
        return e.type !== "text"
    };
    var s = i();
    var o = function(t, n) {
        return typeof t === "undefined" || t === null || t === undefined || t == [] || t === "" || n && e.trim(t) === ""
    };
    var u = function(e, t, n) {
        var r = o(e.data(t)) ? e.attr(t) : e.data(t);
        if (r) {
            return r
        }
        return n[t]
    };
    var a = function() {
        return "kvstar-" + Math.round((new Date).getTime() + Math.random() * 100)
    };
    var f = function(t, n) {
        this.$elementOrig = e(t);
        this.refreshRating = false;
        if (s) {
            this.init(n)
        } else {
            var r = n.starCaptions;
            this.polyfill(r)
        }
    };
    f.prototype = {
        constructor: f,
        _parseAttr: function(e, i) {
            var s = this,
                a = s.$elementOrig;
            if (a.attr("type") === "range" || a.attr("type") === "number") {
                var f = u(a, e, i);
                var l = r;
                if (e === "min") {
                    l = t
                } else if (e === "max") {
                    l = n
                } else if (e === "step") {
                    l = r
                }
                var c = o(f) ? l : f;
                return parseFloat(c)
            }
            return parseFloat(i[e])
        },
        listen: function() {
            var t = this;
            t.$element.on("change", function(e) {
                if (!t.inactive) {
                    t.setStars();
                    t.$elementOrig.val(t.$element.val());
                    t.$elementOrig.trigger("change");
                    t.$elementOrig.trigger("rating.change", [t.$element.val(), t.$caption.html()])
                }
            });
            t.$clear.on("click", function(e) {
                if (!t.inactive) {
                    t.clear()
                }
            });
            e(t.$elementOrig[0].form).on("reset", function(e) {
                if (!t.inactive) {
                    t.reset()
                }
            })
        },
        initSlider: function(i) {
            var s = this,
                u = o(s.$elementOrig.attr("id")) ? a() : "kvstar-" + s.$elementOrig.attr("id");
            if (o(s.$elementOrig.val())) {
                s.$elementOrig.val(0)
            }
            s.initialValue = s.$elementOrig.val();
            s.min = typeof i.min !== "undefined" ? i.min : s._parseAttr("min", i);
            s.max = typeof i.max !== "undefined" ? i.max : s._parseAttr("max", i);
            s.step = typeof i.step !== "undefined" ? i.step : s._parseAttr("step", i);
            if (isNaN(s.min) || o(s.min)) {
                s.min = t
            }
            if (isNaN(s.max) || o(s.max)) {
                s.max = n
            }
            if (isNaN(s.step) || o(s.step) || s.step == 0) {
                s.step = r
            }
            s.$elementOrig.clone(true).attr({
                id: u,
                name: u,
                type: "range"
            }).insertBefore(s.$elementOrig);
            s.$elementOrig.removeAttr("class");
            s.$element = e("#" + u);
            s.$element.attr({
                min: s.min,
                max: s.max,
                step: s.step,
                disabled: s.disabled,
                readonly: s.readonly
            });
            s.$element.val(s.$elementOrig.val());
            s.$elementOrig.hide()
        },
        polyfill: function(t) {
            var n = this,
                r = e("<select>"),
                i = n.$element;
            i.before(r);
            for (var s in t) {
                r.append(e("<option>").attr("value", s).text(t[s]))
            }
            r.attr({
                "class": i.attr("class"),
                style: i.attr("style")
            });
            i.hide();
            r.on("change", function(e) {
                n.$element.val(r.val());
                n.$element.trigger("change");
                n.$element.trigger("rating.change", [r.val(), t[r.val()]])
            })
        },
        init: function(t) {
            this.options = t;
            if (!this.$elementOrig.is(":visible") && !this.refreshRating) {
                return
            }
            this.refreshRating = false;
            if (typeof this.$element == "undefined") {
                this.initSlider(t)
            }
            this.checkDisabled();
            $element = this.$element;
            this.containerClass = t.containerClass;
            this.glyphicon = t.glyphicon;
            var n = this.glyphicon ? "" : "★";
            this.symbol = o(t.symbol) ? n : t.symbol;
            this.rtl = t.rtl || this.$element.attr("dir");
            if (this.rtl) {
                this.$element.attr("dir", "rtl")
            }
            this.showClear = t.showClear;
            this.showCaption = t.showCaption;
            this.size = t.size;
            this.stars = t.stars;
            this.defaultCaption = t.defaultCaption;
            this.starCaptions = t.starCaptions;
            this.starCaptionClasses = t.starCaptionClasses;
            this.clearButton = t.clearButton;
            this.clearButtonTitle = t.clearButtonTitle;
            this.clearButtonBaseClass = !o(t.clearButtonBaseClass) ? t.clearButtonBaseClass : "clear-rating";
            this.clearButtonActiveClass = !o(t.clearButtonActiveClass) ? t.clearButtonActiveClass : "clear-rating-active";
            this.clearCaption = t.clearCaption;
            this.clearCaptionClass = t.clearCaptionClass;
            this.clearValue = t.clearValue;
            this.$clearElement = t.clearElement;
            this.$captionElement = t.captionElement;
            this.$element.removeClass("rating-slider").addClass("rating-slider");
            if (typeof this.$rating == "undefined" && typeof this.$container == "undefined") {
                this.$rating = e(document.createElement("div")).html('<div class="rating-stars"></div>');
                this.$container = e(document.createElement("div"));
                this.$container.before(this.$rating);
                this.$container.append(this.$rating);
                this.$element.before(this.$container).appendTo(this.$rating)
            }
            this.$stars = this.$rating.find(".rating-stars");
            this.generateRating();
            this.$clear = !o(this.$clearElement) ? this.$clearElement : this.$container.find("." + this.clearButtonBaseClass);
            this.$caption = !o(this.$captionElement) ? this.$captionElement : this.$container.find(".caption");
            this.setStars();
            this.listen();
            if (this.showClear) {
                this.$clear.attr({
                    "class": this.getClearClass()
                })
            }
        },
        checkDisabled: function() {
            var e = this;
            e.disabled = u(e.$element, "disabled", e.options);
            e.readonly = u(e.$element, "readonly", e.options);
            e.inactive = e.disabled || e.readonly
        },
        getClearClass: function() {
            return this.clearButtonBaseClass + " " + (this.inactive ? "" : this.clearButtonActiveClass)
        },
        generateRating: function() {
            var e = this,
                t = e.renderClear(),
                n = e.renderCaption(),
                r = e.rtl ? "rating-container-rtl" : "rating-container",
                i = e.getStars();
            r += e.glyphicon ? e.symbol == "" ? " rating-gly-star" : " rating-gly" : " rating-uni";
            e.$rating.attr("class", r);
            e.$rating.attr("data-content", i);
            e.$stars.attr("data-content", i);
            var r = e.rtl ? "star-rating-rtl" : "star-rating";
            e.$container.attr("class", r + " rating-" + e.size);
            if (e.inactive) {
                e.$container.addClass("rating-disabled")
            } else {
                e.$container.removeClass("rating-disabled")
            } if (typeof e.$caption == "undefined" && typeof e.$clear == "undefined") {
                if (e.rtl) {
                    e.$container.prepend(n).append(t)
                } else {
                    e.$container.prepend(t).append(n)
                }
            }
            if (!o(e.containerClass)) {
                e.$container.removeClass(e.containerClass).addClass(e.containerClass)
            }
        },
        getStars: function() {
            var e = this,
                t = e.stars,
                n = "";
            for (var r = 1; r <= t; r++) {
                n += e.symbol
            }
            return n
        },
        renderClear: function() {
            var e = this;
            if (!e.showClear) {
                return ""
            }
            var t = e.getClearClass();
            if (!o(e.$clearElement)) {
                e.$clearElement.removeClass(t).addClass(t).attr({
                    title: e.clearButtonTitle
                });
                e.$clearElement.html(e.clearButton);
                return ""
            }
            return '<div class="' + t + '" title="' + e.clearButtonTitle + '">' + e.clearButton + "</div>"
        },
        renderCaption: function() {
            var e = this,
                t = e.$element.val();
            if (!e.showCaption) {
                return ""
            }
            var n = e.fetchCaption(t);
            if (!o(e.$captionElement)) {
                e.$captionElement.removeClass("caption").addClass("caption").attr({
                    title: e.clearCaption
                });
                e.$captionElement.html(n);
                return ""
            }
            return '<div class="caption">' + n + "</div>"
        },
        fetchCaption: function(e) {
            var t = this;
            var n = parseFloat(e);
            var r = o(t.starCaptionClasses[n]) ? t.clearCaptionClass : t.starCaptionClasses[n];
            var i = !o(t.starCaptions[n]) ? t.starCaptions[n] : t.defaultCaption.replace(/\{rating\}/g, n);
            var s = n == t.clearValue ? t.clearCaption : i;
            return '<span class="' + r + '">' + s + "</span>"
        },
        setStars: function() {
            var e = this,
                t = e.min,
                n = e.max,
                r = e.step,
                i = e.$element.val(),
                s = 0,
                u = e.fetchCaption(i);
            if (i == n) {
                s = 100
            } else if (!o(i) && i >= t) {
                s = Math.floor((i - t) / n * 1e3) / 10
            }
            if (e.rtl) {
                s = 100 - s
            }
            s += "%";
            e.$stars.css("width", s);
            e.$caption.html(u)
        },
        clear: function() {
            var e = this;
            var t = '<span class="' + e.clearCaptionClass + '">' + e.clearCaption + "</span>";
            e.$stars.removeClass("rated");
            if (!e.inactive) {
                e.$caption.html(t)
            }
            e.$element.val(e.clearValue);
            e.$elementOrig.val(e.clearValue);
            e.$elementOrig.trigger("change");
            e.setStars();
            e.$elementOrig.trigger("rating.clear")
        },
        reset: function() {
            var e = this;
            e.$element.val(e.initialValue);
            e.$elementOrig.val(e.initialValue);
            e.setStars();
            e.$elementOrig.trigger("rating.reset")
        },
        update: function(e) {
            if (arguments.length > 0) {
                var t = this;
                t.$element.val(e).change()
            }
        },
        refresh: function(t) {
            var n = this;
            if (arguments.length) {
                this.refreshRating = true;
                var r = "";
                n.init(e.extend(n.options, t));
                if (n.showClear) {
                    n.$clear.show()
                } else {
                    n.$clear.hide()
                } if (n.showCaption) {
                    n.$caption.show()
                } else {
                    n.$caption.hide()
                }
            }
        }
    };
    e.fn.rating = function(t) {
        var n = Array.apply(null, arguments);
        n.shift();
        return this.each(function() {
            var r = e(this),
                i = r.data("rating"),
                s = typeof t === "object" && t;
            if (!i) {
                r.data("rating", i = new f(this, e.extend({}, e.fn.rating.defaults, s, e(this).data())))
            }
            if (typeof t === "string") {
                i[t].apply(i, n)
            }
        })
    };
    e.fn.rating.defaults = {
        stars: 5,
        glyphicon: true,
        symbol: null,
        disabled: false,
        readonly: false,
        rtl: false,
        size: "md",
        showClear: true,
        showCaption: true,
        defaultCaption: "{rating} Stars",
        starCaptions: {.5: "Half Star",
            1: "One Star",
            1.5: "One & Half Star",
            2: "Two Stars",
            2.5: "Two & Half Stars",
            3: "Three Stars",
            3.5: "Three & Half Stars",
            4: "Four Stars",
            4.5: "Four & Half Stars",
            5: "Five Stars"
        },
        starCaptionClasses: {.5: "label label-danger",
            1: "label label-danger",
            1.5: "label label-warning",
            2: "label label-warning",
            2.5: "label label-info",
            3: "label label-info",
            3.5: "label label-primary",
            4: "label label-primary",
            4.5: "label label-success",
            5: "label label-success"
        },
        clearButton: '<i class="glyphicon glyphicon-minus-sign"></i>',
        clearButtonTitle: "Clear",
        clearButtonBaseClass: "clear-rating",
        clearButtonActiveClass: "clear-rating-active",
        clearCaption: "Not Rated",
        clearCaptionClass: "label label-default",
        clearValue: 0,
        captionElement: null,
        clearElement: null,
        containerClass: null
    };
    e(function() {
        var t = s ? e("input.rating[type=number]") : e("input.rating[type=text]");
        if (t.length > 0) {
            t.rating()
        }
    })
})(jQuery)