define(function (require, exports, module) {
  var pageModule = new PageLogic({
    getUrl: "goform/getNAT",
    modules:
      "staticIPList,portList,ddns,dmz,ping,upnp,lanCfg,macFilter,localhost,wifiRelay,onlineList,apIsolation",
    setUrl: "goform/setNAT",
  });
  function updateDDNSStatus(obj) {
    showConnectStatus(obj.ddns.ddnsStatus);
  }
  ((pageModule.initEvent = function () {
    pageModule.update("ddns", 2000, updateDDNSStatus);
  }),
    (pageModule.modules = []),
    (module.exports = pageModule));
  var pageModuleInit = new (function () {
    this.initValue = function () {
      var wifiRelayObj = pageModule.data.wifiRelay;
      ("ap" != wifiRelayObj.wifiRelayType &&
        "client+ap" != wifiRelayObj.wifiRelayType) ||
        $(
          "#staticIPMapping, #protMapping, #ddnsConfig, #dmzHost, #upnp, #pingWan",
        ).addClass("none");
    };
  })();
  pageModule.modules.push(pageModuleInit);
  var macFilter = new (function () {
    var _this = this;
    ((this.moduleName = "macFilter"),
      (this.initValue = function (macFilterObj) {
        ((this.curFilterMode = ""),
          (this.localhostObj = pageModule.data.localhost),
          (this.passListEmpty = !0),
          (this.onlineList = []),
          (this.passMacList = []),
          (this.passMacListHasNative = !1),
          (this.denyMacList = []),
          (this.hasAddedOnlinelist = !1),
          this.transDataList(macFilterObj),
          inputValue(macFilterObj),
          this.changeFilterMode());
      }),
      (this.transDataList = function (obj) {
        var i = 0;
        for (
          this.curFilterMode = obj.curFilterMode,
            this.localhostObj.mac =
              this.localhostObj.localhostMAC.toUpperCase(),
            this.localhostObj.hostname = "",
            this.localhostObj.remark = "",
            this.onlineList = this.getOnlineList(),
            i = 0;
          i < obj.macFilterList.length;
          i++
        ) {
          "pass" == obj.macFilterList[i].filterMode
            ? ((this.passListEmpty = !1),
              obj.macFilterList[i].mac.toUpperCase() ==
                this.localhostObj.mac.toUpperCase() &&
                ((_this.passMacListHasNative = !0),
                (obj.macFilterList[i].isNative = !0)),
              this.passMacList.push(obj.macFilterList[i]))
            : this.denyMacList.push(obj.macFilterList[i]);
        }
        _this.passMacListHasNative ||
          _this.passMacList.unshift(_this.localhostObj);
      }),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initEvent = function () {
        ($("input[name=curFilterMode]").on("click", _this.changeFilterMode),
          $("#macFilterHead").delegate(
            ".icon-add",
            "click",
            _this.addMacFilterList,
          ),
          $("#macFilterBody").delegate(
            ".icon-del",
            "click",
            _this.delMacFilterList,
          ),
          $("#macFilterBody").delegate(".addOnline", "click", function () {
            ((_this.hasAddedOnlinelist = !0),
              (_this.passMacList = _this.passMacList.concat(_this.onlineList)),
              _this.createMacFilterTable(),
              $(this).parent().parent().remove());
          }),
          $("#macFilterHead").delegate("#filterRemark", "keyup", function () {
            var deviceVal = this.value.replace("\t", "").replace("\n", ""),
              len = deviceVal.length,
              totalByte = getStrByteNum(deviceVal);
            if (63 < totalByte) {
              for (var i = len - 1; 0 < i; i--) {
                if ((totalByte -= getStrByteNum(deviceVal[i])) <= 63) {
                  this.value = deviceVal.slice(0, i);
                  break;
                }
              }
            }
            this.value = deviceVal;
          }));
      }),
      (this.delMacFilterList = function () {
        var i,
          key,
          mac = $(this).parent().parent().find(".mac").html(),
          renderList =
            "pass" == _this.curFilterMode
              ? _this.passMacList
              : _this.denyMacList;
        for (i = 0; i < renderList.length; i++) {
          for (key in renderList[i]) {
            if (renderList[i].mac.toUpperCase() == mac) {
              renderList.splice(i, 1);
              break;
            }
          }
        }
        _this.createMacFilterTable();
      }),
      (this.changeFilterMode = function () {
        ($("#filterMac").val(""), $("#filterRemark").val(""));
        var curFilterMode = $("[name=curFilterMode]:checked").val();
        ("deny" == curFilterMode
          ? $("#filterModeDesc").html(_("Blacklisted MAC Address"))
          : $("#filterModeDesc").html(_("Whitelisted MAC Address")),
          (_this.curFilterMode = curFilterMode),
          _this.createMacFilterTable());
      }),
      (this.checkData = function () {}),
      (this.getSubmitData = function () {
        var i = 0,
          data = {},
          tmpList =
            "pass" == _this.curFilterMode
              ? _this.passMacList
              : _this.denyMacList,
          tmpListLen = tmpList.length,
          tmpStr = "";
        for (i = 0; i < tmpListLen; i++) {
          ((tmpStr += tmpList[i].hostname + "\t"),
            (tmpStr += tmpList[i].remark + "\t"),
            (tmpStr += tmpList[i].mac + "\n"));
        }
        tmpStr.replace(/[\n]$/, "");
        var data = {
          module6: _this.moduleName,
          filterMode: _this.curFilterMode,
          macFilterList: tmpStr,
        };
        return objToString(data);
      }),
      (this.createMacFilterTable = function () {
        for (var k = 0; k < _this.passMacList.length; k++) {
          if (
            _this.passMacList[k].mac.toUpperCase() ==
            _this.localhostObj.mac.toUpperCase()
          ) {
            var tmp = _this.passMacList[k];
            (_this.passMacList.splice(k, 1), _this.passMacList.unshift(tmp));
            break;
          }
        }
        var renderStr,
          renderList =
            "deny" == _this.curFilterMode
              ? _this.denyMacList
              : _this.passMacList,
          len = renderList.length,
          i = 0,
          k = 0;
        for ($("#macFilterBody").html(""), i = 0; i < len; i++) {
          ((renderStr = ""),
            (renderStr += "<tr class='listContent'>"),
            (renderStr +=
              '<td class="span-fixed mac">' +
              renderList[i].mac.toUpperCase() +
              "</td>"),
            (renderStr += '<td class="span-fixed remark hidden-xs"></td>'),
            renderList[i].mac.toUpperCase() ==
            _this.localhostObj.mac.toUpperCase()
              ? (renderStr +=
                  "<td class='align-center'>" + _("Local") + "</td>")
              : (renderStr +=
                  "<td class='align-center'><div class='operate icon-del'></div></td>"),
            (renderStr += "</tr>"),
            $("#macFilterBody").append(renderStr),
            $("#macFilterBody").find(".remark").text(renderList[i].remark),
            $("#macFilterBody").find(".remark").removeClass("remark"));
        }
        ("pass" == _this.curFilterMode &&
          _this.passListEmpty &&
          !_this.hasAddedOnlinelist &&
          $("#macFilterBody").append(
            "<tr class='align-center'><td colspan='3' style='text-decoration:underline; color:#0070C9; cursor:pointer;'><span class='addOnline'>" +
              _("Whitelist all the online devices") +
              "</span></td></tr >",
          ),
          top.mainLogic.initModuleHeight());
      }),
      (this.addMacFilterList = function () {
        var msg = (function () {
          var mac = $("#filterMac").val();
          if (
            !/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/.test(mac) &&
            !/^([0-9a-fA-F]{2}-){5}[0-9a-fA-F]{2}$/.test(mac)
          ) {
            return (
              $("#filterMac").focus(),
              _("Please enter a valid MAC address.")
            );
          }
          var subMac1 = (mac = mac.replace(/[-]/g, ":")).split(":")[0];
          if (subMac1.charAt(1) && parseInt(subMac1.charAt(1), 16) % 2 != 0) {
            return (
              $("#filterMac").focus(),
              _("The second character must be even number.")
            );
          }
          if ("00:00:00:00:00:00" === mac) {
            return (
              $("#filterMac").focus(),
              _("MAC can not be 00:00:00:00:00:00.")
            );
          }
          var $listArry = $("#macFilterBody").find(".listContent"),
            len = $listArry.length,
            i = 0;
          for (i = 0; i < len; i++) {
            if (
              $listArry.eq(i).find(".mac").html().toUpperCase() ==
              mac.toUpperCase()
            ) {
              return (
                $("#filterMac").focus(),
                _("This MAC address is used. Please try another.")
              );
            }
          }
          var maxItems = 10;
          "pass" == _this.curFilterMode && (maxItems = 20);
          if ($("#macFilterBody").find(".listContent").length >= maxItems) {
            return _("A maximum of %s entries can be added.", [maxItems]);
          }
          return;
        })();
        if (msg) {
          mainLogic.showModuleMsg(msg);
        } else {
          var tmpObj = {
            hostname: "",
            remark: $("#filterRemark").val(),
            mac: $("#filterMac").val().toUpperCase().replace(/\-/g, ":"),
            filterMode: _this.curFilterMode,
          };
          if (
            _this.localhostObj.mac.toUpperCase() != tmpObj.mac.toUpperCase() ||
            "deny" != _this.curFilterMode
          ) {
            ($("#filterMac").val(""), $("#filterRemark").val(""));
            var renderList =
              "pass" == _this.curFilterMode
                ? _this.passMacList
                : _this.denyMacList;
            (renderList.push(tmpObj),
              _this.createMacFilterTable(),
              top.mainLogic.initModuleHeight());
          } else {
            mainLogic.showModuleMsg(
              _("The MAC address of the local device cannot be blacklisted."),
            );
          }
        }
      }),
      (this.getOnlineList = function () {
        for (
          var onlineList = pageModule.data.onlineList,
            tmpObj = {},
            tmpList = [],
            i = 0;
          i < onlineList.length;
          i++
        ) {
          (tmpObj = {
            hostname: onlineList[i].qosListHostname,
            mac: onlineList[i].qosListMac.toUpperCase(),
            remark: onlineList[i].qosListRemark,
            filterMode: "pass",
          }).mac != pageModule.data.localhost.mac
            ? tmpList.push(tmpObj)
            : (_this.localhostObj = tmpObj);
        }
        return tmpList;
      }));
  })();
  pageModule.modules.push(macFilter);
  var staticModule = new (function () {
    var that = this;
    function delStaticList() {
      ($(this).parent().parent().remove(), top.mainLogic.initModuleHeight());
    }
    function addStaticList() {
      var str,
        msg = (function () {
          var staticIP = $("#staticIp").val(),
            mac = $("#staticMac").val(),
            lanCfgObj = pageModule.data.lanCfg,
            startIP = lanCfgObj.lanDhcpStartIP,
            endIP = lanCfgObj.lanDhcpEndIP,
            lanIP = lanCfgObj.lanIP,
            lanMask = lanCfgObj.lanMask;
          if (
            !/^([1-9]|[1-9]\d|1\d\d|2[0-1]\d|22[0-3])\.(([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.){2}([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/.test(
              staticIP,
            )
          ) {
            return (
              $("#staticIp").focus(),
              _("Please enter a valid IP address.")
            );
          }
          if (!checkIpInSameSegment(staticIP, lanMask, lanIP, lanMask)) {
            return (
              $("#staticIp").focus(),
              _("%s and %s must be in the same network segment.", [
                _("Static IP"),
                _("LAN IP"),
              ])
            );
          }
          var msg = checkIsVoildIpMask(
            staticIP,
            lanMask,
            _("Static IP Address"),
          );
          if (msg) {
            return ($("#staticIp").focus(), msg);
          }
          if (staticIP == lanIP) {
            return (
              $("#staticIp").focus(),
              _("%s cannot be the same as the %s (%s).", [
                _("Static IP Address"),
                _("LAN IP Address"),
                lanIP,
              ])
            );
          }
          var ipNumber,
            sipNumber,
            eipNumber,
            ipArry = staticIP.split("."),
            sipArry = startIP.split("."),
            eipArry = endIP.split(".");
          if (
            ((ipNumber =
              256 * parseInt(ipArry[0], 10) * 256 * 256 +
              256 * parseInt(ipArry[1], 10) * 256 +
              256 * parseInt(ipArry[2], 10) +
              parseInt(ipArry[3], 10)),
            (sipNumber =
              256 * parseInt(sipArry[0], 10) * 256 * 256 +
              256 * parseInt(sipArry[1], 10) * 256 +
              256 * parseInt(sipArry[2], 10) +
              parseInt(sipArry[3], 10)),
            (eipNumber =
              256 * parseInt(eipArry[0], 10) * 256 * 256 +
              256 * parseInt(eipArry[1], 10) * 256 +
              256 * parseInt(eipArry[2], 10) +
              parseInt(eipArry[3], 10)),
            ipNumber < sipNumber || eipNumber < ipNumber)
          ) {
            return (
              $("#staticIp").focus(),
              _("The IP address must be included in the address pool of DHCP.")
            );
          }
          if (
            !/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/.test(mac) &&
            !/^([0-9a-fA-F]{2}-){5}[0-9a-fA-F]{2}$/.test(mac)
          ) {
            return (
              $("#staticMac").focus(),
              _("Please enter a valid MAC address.")
            );
          }
          var subMac1 = (mac = mac.replace(/[-]/g, ":")).split(":")[0];
          if (subMac1.charAt(1) && parseInt(subMac1.charAt(1), 16) % 2 != 0) {
            return (
              $("#staticMac").focus(),
              _("The second character must be even number.")
            );
          }
          if ("00:00:00:00:00:00" === mac) {
            return (
              $("#staticMac").focus(),
              _("MAC can not be 00:00:00:00:00:00.")
            );
          }
          var listMac,
            listIp,
            $listArry = $("#staticTbody").children(),
            len = $listArry.length,
            i = 0;
          for (i = 0; i < len; i++) {
            if (
              ((listIp = $listArry.eq(i).children().eq(0).html()),
              (listMac = $listArry.eq(i).children().eq(1).html()),
              staticIP == listIp)
            ) {
              return (
                $("#staticIp").focus(),
                _("This IP address is used. Please try another.")
              );
            }
            if (listMac.toUpperCase() == mac.toUpperCase()) {
              return (
                $("#staticMac").focus(),
                _("This MAC address is used. Please try another.")
              );
            }
          }
          if (20 <= $("#staticTbody").children().length) {
            return _("A maximum of %s entries can be added.", [20]);
          }
          return;
        })();
      msg
        ? mainLogic.showModuleMsg(msg)
        : ((str = "<tr>"),
          (str += '<td class="span-fixed">' + $("#staticIp").val() + "</td>"),
          (str +=
            '<td class="span-fixed">' +
            $("#staticMac").val().replace(/[-]/g, ":").toUpperCase() +
            "</td>"),
          (str +=
            "<td class='align-center'><div class='operate icon-del'></div></td>"),
          (str += "</tr>"),
          $("#staticTbody").append(str),
          $("#staticIp").val(""),
          $("#staticMac").val(""),
          top.mainLogic.initModuleHeight());
    }
    ((this.moduleName = "staticIPList"),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initEvent = function () {
        ($("#staticHead").delegate(".icon-add", "click", addStaticList),
          $("#staticTbody").delegate(".icon-del", "click", delStaticList));
      }),
      (this.initValue = function (staticIPListArray) {
        var i = 0,
          len = staticIPListArray.length;
        for (
          $("#staticIp, #staticMac").val(""), $("#staticTbody").html(""), i = 0;
          i < len;
          i++
        ) {
          ((listStr = ""),
            (listStr += "<tr>"),
            (listStr +=
              '<td class="span-fixed">' +
              staticIPListArray[i].staticIP +
              "</td>"),
            (listStr +=
              '<td class="span-fixed">' +
              staticIPListArray[i].staticMac.toUpperCase() +
              "</td>"),
            (listStr +=
              "<td class='align-center'><div class='operate icon-del'></div></td>"),
            (listStr += "</tr>"),
            $("#staticTbody").append(listStr));
        }
      }),
      (this.checkData = function () {}),
      (this.getSubmitData = function () {
        if (
          "ap" == pageModule.data.wifiRelay.wifiRelayType ||
          "client+ap" == pageModule.data.wifiRelay.wifiRelayType
        ) {
          return "";
        }
        var data = {
          module1: that.moduleName,
          staticList: (function () {
            var str = "",
              i = 0,
              $staticArry = $("#staticTbody").children(),
              length = $staticArry.length;
            for (i = 0; i < length; i++) {
              ((str += $staticArry.eq(i).children().eq(0).html() + "\t"),
                (str +=
                  $staticArry.eq(i).children().eq(1).html().toUpperCase() +
                  "\t"),
                (str += $staticArry.eq(i).children().eq(2).text() + "\t"),
                (str += "\n"));
            }
            return (str = str.replace(/[\n]$/, ""));
          })(),
        };
        return objToString(data);
      }));
  })();
  pageModule.modules.push(staticModule);
  var portMap = new (function () {
    var that = this;
    function addPortList() {
      var str = "",
        inIp = $("#internalIP").val(),
        inPort = $("#internalPort")[0].val(),
        outPort = $("#externalPort").val(),
        protocol = $("#protocol").val(),
        msg = (function () {
          var inIp = $("#internalIP").val(),
            inPort = $("#internalPort")[0].val(),
            outPort = $("#externalPort").val(),
            lanIP = pageModule.data.lanCfg.lanIP,
            lanMask = pageModule.data.lanCfg.lanMask,
            k = 0,
            portArry = $("#portTbody").children(),
            length = portArry.length,
            existExternalPort = "";
          for (k = 0; k < length; k++) {
            if (
              ((existExternalPort = portArry.eq(k).children().eq("2").html()),
              outPort == existExternalPort)
            ) {
              return _("The external port %s already exists.", [outPort]);
            }
          }
          if (
            !/^([1-9]|[1-9]\d|1\d\d|2[0-1]\d|22[0-3])\.(([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.){2}([0-9]|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])$/.test(
              inIp,
            )
          ) {
            return (
              $("#internalIP").focus(),
              _("Please enter a valid IP address.")
            );
          }
          if (!checkIpInSameSegment(inIp, lanMask, lanIP, lanMask)) {
            return (
              $("#internalIP").focus(),
              _("%s and %s must be in the same network segment.", [
                _("Internal IP Address"),
                _("LAN IP Address"),
              ])
            );
          }
          var msg = checkIsVoildIpMask(inIp, lanMask, _("Internal IP Address"));
          if (msg) {
            return ($("#internalIP").focus(), msg);
          }
          if (inIp == lanIP) {
            return _(
              "The internal IP address cannot be the same as the login IP address (%s).",
              [lanIP],
            );
          }
          if (
            "" == inPort ||
            65535 < parseInt(inPort, 10) ||
            parseInt(inPort, 10) < 1
          ) {
            return (
              $("#internalPort").find(".input-box").focus(),
              _("Internal port range: 1-65535")
            );
          }
          if (
            "" == outPort ||
            65535 < parseInt(outPort, 10) ||
            parseInt(outPort, 10) < 1
          ) {
            return (
              $("#externalPort").focus(),
              _("External Port Range: 1-65535")
            );
          }
          if (16 <= $("#portTbody").children().length) {
            return _("A maximum of %s entries can be added.", [16]);
          }
          return;
        })();
      msg
        ? mainLogic.showModuleMsg(msg)
        : ((str += "<tr>"),
          (str += "<td>" + inIp + "</td>"),
          (str += "<td>" + inPort + "</td>"),
          (str += "<td>" + outPort + "</td>"),
          (str +=
            "<td data-val='" +
            protocol +
            "'>" +
            $("#protocol option:selected").html() +
            "</td>"),
          (str +=
            "<td class='align-center'><div class='operate icon-del'></div></td>"),
          (str += "</tr>"),
          $("#portTbody").append(str),
          $("#internalIP").val(""),
          top.mainLogic.initModuleHeight());
    }
    function delPortList() {
      ($(this).parent().parent().remove(), top.mainLogic.initModuleHeight());
    }
    ((this.moduleName = "portList"),
      (this.init = function () {
        (this.initHtml(), this.initEvent());
      }),
      (this.initHtml = function () {
        var selectObj = {
          initVal: "21",
          editable: "1",
          size: "small",
          seeAsTrans: !0,
          options: [
            {
              21: "21 (FTP)",
              23: "23 (TELNET)",
              25: "25 (SMTP)",
              53: "53 (DNS)",
              80: "80 (HTTP)",
              1723: "1723 (PPTP)",
              3389: _("3389 (Remote Desktop)"),
              9000: "9000",
              9001: "9001",
              ".divider": ".divider",
              ".hand-set": _("Manual"),
            },
          ],
        };
        ($("#internalPort").toSelect(selectObj), $("#externalPort").val("21"));
      }),
      (this.initEvent = function () {
        (($.validate.valid.ddns = function (str) {
          var ret;
          return (ret = $.validate.valid.ascii(str))
            ? ret
            : (ret = $.validate.valid.remarkTxt(str, ";")) || void 0;
        }),
          $("#portHead").delegate(".icon-add", "click", addPortList),
          $("#portTbody").delegate(".icon-del", "click", delPortList),
          $("#internalPort .input-box, #externalPort").on("keyup", function () {
            this.value = parseInt(this.value, 10) || "";
          }),
          $("#internalPort .input-box, #externalPort").on("blur", function () {
            this.value = parseInt(this.value, 10) || "";
          }),
          $("#internalPort.input-append ul li").on("click", function (e) {
            $("#externalPort")[0].value =
              ".hand-set" == $(this).attr("data-val")
                ? $("#externalPort").val()
                : $(this).attr("data-val");
          }));
      }),
      (this.initValue = function (portListArray) {
        var listArry = portListArray,
          len = listArry.length,
          i = 0,
          str = "";
        for (
          $("#internalIP").val(""),
            $("#internalPort input[type=text]").val("21"),
            $("#internalPort input[type=hidden]").val("21"),
            $("#externalPort").val("21"),
            $("#protocol").val("both"),
            $("#portTbody").html(""),
            i = 0;
          i < len;
          i++
        ) {
          ((str += "<tr>"),
            (str += "<td>" + listArry[i].portListIntranetIP + "</td>"),
            (str += "<td>" + listArry[i].portListIntranetPort + "</td>"),
            (str += "<td>" + listArry[i].portListExtranetPort + "</td>"),
            (str +=
              "<td data-val='" +
              listArry[i].portListProtocol +
              "'>" +
              $(
                "#protocol [value='" + listArry[i].portListProtocol + "']",
              ).html() +
              "</td>"),
            (str +=
              "<td class='align-center'><div class='operate icon-del' title='" +
              _("Delete") +
              "'></div></td>"),
            (str += "</tr>"));
        }
        $("#portTbody").html(str);
      }),
      (this.checkData = function () {}),
      (this.getSubmitData = function () {
        if (
          "ap" == pageModule.data.wifiRelay.wifiRelayType ||
          "client+ap" == pageModule.data.wifiRelay.wifiRelayType
        ) {
          return "";
        }
        var data = {
          module2: that.moduleName,
          portList: (function () {
            var str = "",
              i = 0,
              $portArry = $("#portTbody").children(),
              length = $portArry.length;
            ($("#internalIP").val(),
              $("#internalPort")[0].val(),
              $("#externalPort").val(),
              $("#protocol").val());
            for (i = 0; i < length; i++) {
              ((str += $portArry.eq(i).children().eq(0).html() + ";"),
                (str += $portArry.eq(i).children().eq(1).html() + ";"),
                (str += $portArry.eq(i).children().eq(2).html() + ";"),
                (str += $portArry.eq(i).children().eq(3).attr("data-val")),
                (str += "~"));
            }
            return (str = str.replace(/[~]$/, ""));
          })(),
        };
        return objToString(data);
      }));
  })();
  pageModule.modules.push(portMap);
  var dmzModule = new (function () {
    var that = this;
    function changeDmzEn() {
      var dmzEn = $("input[name='dmzEn']:checked").val();
      ("true" == dmzEn
        ? $("#dmzWrap").removeClass("none")
        : $("#dmzWrap").addClass("none"),
        top.mainLogic.initModuleHeight());
    }
    ((this.moduleName = "dmz"),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initEvent = function () {
        $("input[name='dmzEn']").on("click", changeDmzEn);
      }),
      (this.initValue = function (dmzObj) {
        ($("#dmzHostIP").removeValidateTipError(!0),
          ($("input[name='dmzEn'][value='" + dmzObj.dmzEn + "']")[0].checked =
            !0),
          $("#dmzHostIP").val(dmzObj.dmzHostIP || ""),
          changeDmzEn());
      }),
      (this.checkData = function () {
        if (
          "ap" != pageModule.data.wifiRelay.wifiRelayType &&
          "client+ap" != pageModule.data.wifiRelay.wifiRelayType
        ) {
          var dmzIP = $("#dmzHostIP").val(),
            lanIP = pageModule.data.lanCfg.lanIP,
            lanMask = pageModule.data.lanCfg.lanMask;
          if ($("input[name='dmzEn']")[0].checked) {
            if (!checkIpInSameSegment(dmzIP, lanMask, lanIP, lanMask)) {
              return (
                $("#dmzHostIP").focus(),
                _("%s and %s must be in the same network segment.", [
                  _("Host IP Address"),
                  _("LAN IP Address"),
                ])
              );
            }
            var msg = checkIsVoildIpMask(dmzIP, lanMask, _("Host IP Address"));
            if (msg) {
              return ($("#dmzHostIP").focus(), msg);
            }
            if (dmzIP == lanIP) {
              return _(
                "The DMZ host IP address cannot be the same as the login IP address (%s).",
                [lanIP],
              );
            }
          }
        }
      }),
      (this.getSubmitData = function () {
        if (
          "ap" == pageModule.data.wifiRelay.wifiRelayType ||
          "client+ap" == pageModule.data.wifiRelay.wifiRelayType
        ) {
          return "";
        }
        var data = {
          module3: that.moduleName,
          dmzEn: $("input[name='dmzEn']:checked").val(),
          dmzHostIP: $("#dmzHostIP").val(),
        };
        return objToString(data);
      }));
  })();
  pageModule.modules.push(dmzModule);
  var ddnsModule = new (function () {
    var that = this;
    ((this.moduleName = "ddns"),
      (this.data = {}),
      (this.init = function () {
        ((this.addInputEvent = !1),
          this.addInputEvent ||
            ($("#ddnsPwd").initPassword(), (this.addInputEvent = !0)),
          this.initEvent());
      }),
      (this.initEvent = function () {
        ($("input[name='ddnsEn']").on("click", this.changeDdnsEn),
          $("#ddnsServiceName").on("change", this.changeDdnsServiceName),
          $("#register").on("click", function () {
            var url = $("#ddnsServiceName").val();
            window.open("http://" + url);
          }));
      }),
      (this.initValue = function (ddnsObj) {
        ($("#ddnsUser, #ddnsPwd, #ddnsServer").removeValidateTipError(!0),
          (this.data = ddnsObj),
          $("html").hasClass("lang-cn") &&
            $("#ddnsConnectionStatusInfo").html("连接状态"),
          inputValue(this.data),
          "" != $("#ddnsPwd").val()
            ? $("#ddnsPwd").parent().find(".placeholder-content").hide()
            : $("#ddnsPwd").parent().find(".placeholder-content").show(),
          showConnectStatus(ddnsObj.ddnsStatus),
          this.changeDdnsEn());
        var ddnsServiceName = $("#ddnsServiceName").val();
        "3322.org" == ddnsServiceName ||
        "no-ip.com" == ddnsServiceName ||
        "dyn.com" == ddnsServiceName
          ? $("#ddnsDomain").removeClass("none")
          : $("#ddnsDomain").addClass("none");
      }),
      (this.checkData = function () {}),
      (this.getSubmitData = function () {
        if (
          "ap" == pageModule.data.wifiRelay.wifiRelayType ||
          "client+ap" == pageModule.data.wifiRelay.wifiRelayType
        ) {
          return "";
        }
        var data = {
          module4: that.moduleName,
          ddnsEn: $("input[name='ddnsEn']:checked").val(),
          ddnsServiceName: $("#ddnsServiceName").val(),
          ddnsServer: $("#ddnsServer").val(),
          ddnsUser: $("#ddnsUser").val(),
          ddnsPwd: $("#ddnsPwd").val(),
        };
        return objToString(data);
      }),
      (this.changeDdnsServiceName = function () {
        var domainName = $("#ddnsServiceName").val();
        ($("#ddnsUser, #ddnsPwd, #ddnsServer").removeValidateTipError(!0),
          domainName == that.data.ddnsServiceName
            ? ($("#ddnsUser").val(that.data.ddnsUser),
              $("#ddnsPwd").val(that.data.ddnsPwd),
              $("#ddnsServer").val(that.data.ddnsServer))
            : ($("#ddnsUser").val(""), $("#ddnsPwd").val("")),
          "3322.org" == domainName ||
          "no-ip.com" == domainName ||
          "dyn.com" == domainName
            ? $("#ddnsDomain").removeClass("none")
            : $("#ddnsDomain").addClass("none"),
          top.mainLogic.initModuleHeight());
      }),
      (this.changeDdnsEn = function () {
        var ddnsEn = $("input[name='ddnsEn']:checked").val();
        ("true" == ddnsEn
          ? $("#ddnsWrap").removeClass("none")
          : $("#ddnsWrap").addClass("none"),
          top.mainLogic.initModuleHeight());
      }));
  })();
  function showConnectStatus(status) {
    var str = "";
    ($("html").hasClass("lang-cn")
      ? (stArr = {
          Disconnected: "未连接",
          Connectting: "连接中",
          Connected: "已连接",
        })
      : (stArr = {
          Disconnected: _("Disconnected"),
          Connectting: _("Connecting"),
          Connected: _("Connected"),
        }),
      (str =
        "Connected" == status
          ? "text-success"
          : "Connectting" == status
            ? "text-primary"
            : "text-danger"),
      $("#ddnsStatus").attr("class", str).html(stArr[status]));
  }
  pageModule.modules.push(ddnsModule);
  var upnpModule = new (function () {
    var that = this;
    ((this.moduleName = "upnp"),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initEvent = function () {}),
      (this.initValue = function (upnpObj) {
        inputValue(upnpObj);
      }),
      (this.checkData = function () {}),
      (this.getSubmitData = function () {
        if (
          "ap" == pageModule.data.wifiRelay.wifiRelayType ||
          "client+ap" == pageModule.data.wifiRelay.wifiRelayType
        ) {
          return "";
        }
        var data = {
          module5: that.moduleName,
          upnpEn: $("input[name='upnpEn']:checked").val(),
        };
        return objToString(data);
      }));
  })();
  pageModule.modules.push(upnpModule);
  var pingModule = new (function () {
    var that = this;
    if (
      ((this.moduleName = "ping"),
      (this.init = function () {
        this.initEvent();
      }),
      (this.initEvent = function () {}),
      (this.initValue = function (pingObj) {
        inputValue(pingObj);
      }),
      (this.checkData = function () {}),
      (this.getSubmitData = function () {
        var data = {
          module7: that.moduleName,
          pingEn: $("input[name='pingEn']:checked").val(),
        };
        return objToString(data);
      }),
      "y" === CONFIG_APISOLATION)
    ) {
      var apIsolationModule = new (function () {
        var that = this;
        ((this.moduleName = "apIsolation"),
          (this.init = function () {}),
          (this.initValue = function (apIsolationObj) {
            ($("#apIsolation").removeClass("none"), inputValue(apIsolationObj));
          }),
          (this.checkData = function () {}),
          (this.getSubmitData = function () {
            var data = {
              module8: that.moduleName,
              apIsolationEn: $("input[name='apIsolationEn']:checked").val(),
            };
            return objToString(data);
          }));
      })();
      pageModule.modules.push(apIsolationModule);
    }
  })();
  pageModule.modules.push(pingModule);
});
