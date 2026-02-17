define(function (require, exports, module) {
  var pageModule = new PageLogic({
    getUrl: "goform/getWifi",
    modules:
      "wifiEn,wifiBasicCfg,wifiAdvCfg,wifiPower,wifiTime,wifiWPS,multiSSID",
    setUrl: "goform/setWifi",
  });
  function update(obj) {
    ("0" == obj.wifiAdvCfg.wifiChannelCurrent
      ? $("#wifiBandwidthCurrent, #wifiChannelCurrent").addClass("none")
      : $("#wifiBandwidthCurrent, #wifiChannelCurrent").removeClass("none"),
      $("#wifiChannelCurrent").text(obj.wifiAdvCfg.wifiChannelCurrent),
      $("#wifiBandwidthCurrent").text(
        "" == obj.wifiAdvCfg.wifiBandwidthCurrent
          ? ""
          : obj.wifiAdvCfg.wifiBandwidthCurrent + "MHz",
      ),
      changeWifiRelayType(obj.wifiTime.wifiRelayType));
  }
  function changeWifiRelayType(type) {
    switch (
      ($("#wifiScheduleWrap, #wifiParamWrap, #wpsWrap").addClass("none"), type)
    ) {
      case "disabled":
        ($("#wifiScheduleWrap, #wifiParamWrap").removeClass("none"),
          "y" === CONFIG_HASWPS && $("#wpsWrap").removeClass("none"));
        break;
      case "wisp":
      case "client+ap":
        break;
      default:
        ($("#wifiScheduleWrap, #wifiParamWrap").removeClass("none"),
          "y" === CONFIG_HASWPS && $("#wpsWrap").removeClass("none"));
    }
    top.mainLogic.initModuleHeight();
  }
  ((pageModule.modules = []),
    (pageModule.rebootIP = location.host),
    ((module.exports = pageModule).initEvent = function () {
      (pageModule.update("wifiAdvCfg,wifiTime", 2000, update),
        "n" == CONFIG_WIFI_POW_STRENGTH_WEB && $("#wifiSignal").remove());
    }),
    (pageModule.beforeSubmit = function () {
      var wifiPwd = $("#wifiPwd").val(),
        pwd = pageModule.data.wifiBasicCfg.wifiPwd,
        wifiSecurityMode = $("#wifiSecurityMode").val();
      return !(
        (pageModule.data.wifiBasicCfg.wifiSSID != $("#wifiSSID").val() ||
          (wifiPwd !== pwd && "none" != wifiSecurityMode)) &&
        !confirm(
          _("The wireless connection will be released. Please connect again."),
        )
      );
    }));
  var pageModuleInit = new (function () {
    this.initValue = function () {
      var type = pageModule.data.wifiTime.wifiRelayType;
      changeWifiRelayType(type);
    };
  })();
  pageModule.modules.push(pageModuleInit);
  var wifiEnModule = new (function () {
    function changeWifiEn() {
      ($("#wifiEn").hasClass("icon-toggle-off")
        ? ($("#wifiWrap").show(),
          $("#wifiEn")
            .removeClass("icon-toggle-off")
            .addClass("icon-toggle-on"))
        : ($("#wifiWrap").hide(),
          $("#wifiEn")
            .removeClass("icon-toggle-on")
            .addClass("icon-toggle-off")),
        top.mainLogic.initModuleHeight());
    }
    ((this.moduleName = "wifiEn"),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initValue = function (obj) {
        ($("#wifiEn").html(""),
          "true" == obj.wifiEn
            ? ($("#wifiWrap").show(),
              $("#wifiEn")
                .removeClass("icon-toggle-off")
                .addClass("icon-toggle-on"))
            : ($("#wifiWrap").hide(),
              $("#wifiEn")
                .removeClass("icon-toggle-on")
                .addClass("icon-toggle-off")));
      }),
      (this.initEvent = function () {
        $("#wifiEn").on("click", changeWifiEn);
      }),
      (this.getSubmitData = function () {
        var data = {
          module1: this.moduleName,
          wifiEn: $("#wifiEn").hasClass("icon-toggle-on") || "false",
        };
        return objToString(data);
      }));
  })();
  pageModule.modules.push(wifiEnModule);
  var wifiBasicCfgModule = new (function () {
    var _this = this;
    ((this.moduleName = "wifiBasicCfg"),
      (this.init = function () {
        ((this.addInputEvent = !1),
          this.addInputEvent ||
            ($("#wifiSSID").addPlaceholder(_("WiFi Name")),
            $("#wifiPwd").val("12345678").initPassword(_("WiFi Password")),
            (this.addInputEvent = !0)),
          this.initEvent());
      }),
      (this.initEvent = function () {
        ((this.addInputEvent = !1),
          $("#wifiSecurityMode").on("change", _this.changeSecurityMode),
          $("#helpTips").on("mouseover", function () {
            $("#hideSSIDTips").show();
          }),
          $("#helpTips").on("mouseout", function () {
            $("#hideSSIDTips").hide();
          }));
      }),
      (this.initValue = function (obj) {
        ($("#wifiSSID").removeValidateTipError(!0),
          inputValue(obj),
          "" != $("#wifiSSID").val()
            ? $("#wifiSSID").parent().find(".placeholder-content").hide()
            : $("#wifiSSID").parent().find(".placeholder-content").show(),
          "" != $("#wifiPwd").val()
            ? $("#wifiPwd").parent().find(".placeholder-content").hide()
            : $("#wifiPwd").parent().find(".placeholder-content").show(),
          _this.changeSecurityMode());
      }),
      (this.getSubmitData = function () {
        var data = {
          module2: this.moduleName,
          wifiSSID: $("#wifiSSID").val(),
          wifiSecurityMode: $("#wifiSecurityMode").val(),
          wifiPwd: $("#wifiPwd").val(),
          wifiHideSSID: $("#wifiHideSSID:checked").val() || "false",
        };
        return objToString(data);
      }),
      (this.changeSecurityMode = function () {
        var securityMode = $("#wifiSecurityMode").val();
        ("none" != securityMode
          ? $("#wifiPwd").parent().parent().removeClass("none")
          : $("#wifiPwd").parent().parent().addClass("none"),
          "wpa-psk" != securityMode ? $("#wps").show() : $("#wps").hide(),
          top.mainLogic.initModuleHeight());
      }));
  })();
  if (
    (pageModule.modules.push(wifiBasicCfgModule),
    "y" == CONFIG_WIFI_POW_STRENGTH_WEB)
  ) {
    var wifiPowerModule = new (function () {
      ((this.moduleName = "wifiPower"),
        (this.initValue = function (obj) {
          var gear;
          (inputValue(obj),
            "hide_power" == (gear = obj.wifiPowerGear)
              ? $("#wifiSignal").hide()
              : "hide_normal" == gear && $("#wifiPowerNormalWrap").hide());
        }),
        (this.getSubmitData = function () {
          var data = {
            module6: this.moduleName,
            wifiPower: $("input[name=wifiPower]:checked")[0].value,
          };
          return objToString(data);
        }));
    })();
    pageModule.modules.push(wifiPowerModule);
  }
  if ("y" === window.CONFIG_MBSSID) {
    var multiwifiBasicCfgModule = new (function () {
      var _this = this;
      ((this.moduleName = "multiSSID"),
        (this.init = function () {
          ($("#multiSSID").removeClass("none"),
            $("#multiWifiSSID").addPlaceholder(_("WiFi Name")),
            $("#multiWifiPwd").val("12345678").initPassword(_("WiFi Password")),
            this.initEvent());
        }),
        (this.initEvent = function () {
          $("[name='multiWifiEn']").on("click", _this.changeMultiWIFIEn);
        }),
        (this.changeMultiWIFIEn = function () {
          var multiWIFIEn = $("[name='multiWifiEn']:checked")[0].value;
          "1" == multiWIFIEn
            ? $("#multiCfg").removeClass("none")
            : $("#multiCfg").addClass("none");
        }),
        (this.initValue = function (obj) {
          ($("#multiWifiSSID, #multiWifiPwd").removeValidateTipError(!0),
            inputValue(obj),
            "" != $("#multiWifiSSID").val()
              ? $("#multiWifiSSID").parent().find(".placeholder-content").hide()
              : $("#multiWifiSSID")
                  .parent()
                  .find(".placeholder-content")
                  .show(),
            "" != $("#multiWifiPwd").val()
              ? $("#multiWifiPwd").parent().find(".placeholder-content").hide()
              : $("#multiWifiPwd").parent().find(".placeholder-content").show(),
            ($(
              "[name=multiWifiEn][value='" + obj.multiWifiEnable + "']",
            )[0].checked = !0),
            _this.changeMultiWIFIEn());
        }),
        (this.getSubmitData = function () {
          var data = {
            module7: this.moduleName,
            multiWifiEnable: $("[name='multiWifiEn']:checked").val(),
            multiWifiSSID: $("#multiWifiSSID").val(),
            multiWifiPwd: $("#multiWifiPwd").val(),
          };
          return objToString(data);
        }));
    })();
    pageModule.modules.push(multiwifiBasicCfgModule);
  }
  var wifiTimeModule = new (function () {
    function changeWifiTimeEn() {
      ($("input[name='wifiTimeEn']")[0].checked
        ? $("#wifiScheduleCfg").show()
        : $("#wifiScheduleCfg").hide(),
        top.mainLogic.initModuleHeight());
    }
    function getScheduleDate() {
      var i = 0,
        str = "";
      for (i = 0; i < 8; i++) {
        $("#day" + i)[0].checked ? (str += "1") : (str += "0");
      }
      return str;
    }
    function clickTimeDay() {
      var dataStr = getScheduleDate();
      "day0" == this.id
        ? this.checked
          ? translateDate("11111111")
          : translateDate("00000000")
        : "1111111" == dataStr.slice(1)
          ? translateDate("11111111")
          : translateDate("0" + dataStr.slice(1));
    }
    function translateDate(str) {
      var dayArry = str.split(""),
        len = dayArry.length,
        i = 0;
      for (i = 0; i < len; i++) {
        $("#day" + i)[0].checked = 1 == dayArry[i];
      }
    }
    ((this.moduleName = "wifiTime"),
      (this.init = function () {
        (this.initHtml(), this.initEvent());
      }),
      (this.initHtml = function () {
        var hourStr = "",
          minStr = "",
          i = 0;
        for (i = 0; i < 24; i++) {
          hourStr +=
            "<option value='" +
            ("100" + i).slice(-2) +
            "'>" +
            ("100" + i).slice(-2) +
            "</option>";
        }
        for ($("#startHour, #endHour").html(hourStr), i = 0; i < 60; i++) {
          i % 5 == 0 &&
            (minStr +=
              "<option value='" +
              ("100" + i).slice(-2) +
              "'>" +
              ("100" + i).slice(-2) +
              "</option>");
        }
        $("#startMin, #endMin").html(minStr);
      }),
      (this.initEvent = function () {
        ($("input[name='wifiTimeEn']").on("click", changeWifiTimeEn),
          $("[id^=day]").on("click", clickTimeDay));
      }),
      (this.initValue = function (obj) {
        (inputValue(obj), translateDate(obj.wifiTimeDate), obj.wifiTimeDate);
        var time = obj.wifiTimeClose.split("-");
        ($("#startHour").val(time[0].split(":")[0]),
          $("#startMin").val(time[0].split(":")[1]),
          $("#endHour").val(time[1].split(":")[0]),
          $("#endMin").val(time[1].split(":")[1]),
          changeWifiTimeEn());
      }),
      (this.checkData = function () {
        if (
          "wisp" != pageModule.data.wifiTime.wifiRelayType &&
          "client+ap" != pageModule.data.wifiTime.wifiRelayType &&
          $("[name='wifiTimeEn']")[0].checked
        ) {
          var date = getScheduleDate();
          if ("00000000" == date) {
            return _("Select at least one day.");
          }
          if (
            $("#startHour").val() + ":" + $("#startMin").val() ==
            $("#endHour").val() + ":" + $("#endMin").val()
          ) {
            return _("The end time and start time cannot be the same.");
          }
        }
      }),
      (this.getSubmitData = function () {
        if (
          "wisp" == pageModule.data.wifiTime.wifiRelayType ||
          "client+ap" == pageModule.data.wifiTime.wifiRelayType
        ) {
          return "";
        }
        var time =
            $("#startHour").val() +
            ":" +
            $("#startMin").val() +
            "-" +
            $("#endHour").val() +
            ":" +
            $("#endMin").val(),
          data = {
            module3: this.moduleName,
            wifiTimeEn: $("input[name='wifiTimeEn']:checked").val() || "false",
            wifiTimeClose: time,
            wifiTimeDate: getScheduleDate(),
          };
        return objToString(data);
      }));
  })();
  pageModule.modules.push(wifiTimeModule);
  var wifiAdvCfgModule = new (function () {
    function changeWifiMode(mod) {
      var mode = mod || $("#wifiMode").val();
      "bgn" === mode
        ? $("#wifiBandwidth").html(
            '<option value="auto">' +
              _("Auto") +
              '</option><option value="20">20MHz</option><option value="40">40MHz</option>',
          )
        : $("#wifiBandwidth").html('<option value="20">20MHz</option>');
    }
    ((this.moduleName = "wifiAdvCfg"),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initEvent = function () {
        $("#wifiMode").on("change", function () {
          changeWifiMode();
        });
      }),
      (this.initValue = function (obj) {
        (-1 == obj.wifiBandwidthCurrent.indexOf("MHz") &&
          (obj.wifiBandwidthCurrent = obj.wifiBandwidthCurrent + "MHz"),
          (function (max) {
            var str = '<option value="auto">' + _("Auto") + "</option>";
            for (i = 1; i <= max; i++) {
              str +=
                '<option value="' + i + '">' + _("Channel ") + i + "</option>";
            }
            $("#wifiChannel").html(str);
          })(+obj.wifiMaxChannel),
          changeWifiMode(obj.wifiMode),
          inputValue(obj),
          "0" == obj.wifiChannelCurrent
            ? $("#wifiBandwidthCurrent, #wifiChannelCurrent").addClass("none")
            : $("#wifiBandwidthCurrent, #wifiChannelCurrent").removeClass(
                "none",
              ));
      }),
      (this.getSubmitData = function () {
        if (
          "wisp" == pageModule.data.wifiTime.wifiRelayType ||
          "client+ap" == pageModule.data.wifiTime.wifiRelayType
        ) {
          return "";
        }
        var data = {
          module4: this.moduleName,
          wifiMode: $("#wifiMode").val(),
          wifiChannel: $("#wifiChannel").val(),
          wifiBandwidth: $("#wifiBandwidth").val(),
        };
        return objToString(data);
      }));
  })();
  if ((pageModule.modules.push(wifiAdvCfgModule), "y" === CONFIG_HASWPS)) {
    var wifiWPSModule = new (function () {
      function changeWpsEn() {
        (0 < $("input[name='wpsEn']").length &&
        $("input[name='wpsEn']")[0].checked
          ? $("#wpsCfg").removeClass("none")
          : $("#wpsCfg").addClass("none"),
          top.mainLogic.initModuleHeight());
      }
      ((this.moduleName = "wifiWPS"),
        (this.init = function () {
          this.initEvent();
        }),
        (this.initEvent = function () {
          ($("input[name='wpsEn']").on("click", changeWpsEn),
            $("#wpsPBC").on("click", function () {
              ($("#wpsPBC").attr("disabled", !0),
                $.post("goform/setWifiWps", "action=pbc", function (msg) {
                  checkIsTimeOut(msg)
                    ? top.location.reload(!0)
                    : (mainLogic.showModuleMsg(
                        _("PBC is configured successfully."),
                      ),
                      $("#wpsPBC").removeAttr("disabled"));
                }));
            }));
        }),
        (this.initValue = function (obj) {
          (inputValue(obj), changeWpsEn());
        }),
        (this.getSubmitData = function () {
          if (
            "wisp" == pageModule.data.wifiTime.wifiRelayType ||
            "client+ap" == pageModule.data.wifiTime.wifiRelayType
          ) {
            return "";
          }
          var data = {
            module5: this.moduleName,
            wpsEn: $("input[name='wpsEn']:checked").val() || "false",
          };
          return objToString(data);
        }));
    })();
    pageModule.modules.push(wifiWPSModule);
  }
});
